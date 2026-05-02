import { BrowserMultiFormatReader } from '@zxing/browser';
import { NotFoundException } from '@zxing/library';

let reader = null;
let scannerControls = null;
let isScanning = false;
let hasDetectedBarcode = false;
let activeScanId = 0;

async function startScanner() {
    if (isScanning) return;

    const video = document.getElementById('scanner-video');
    const container = document.getElementById('scanner-container');
    if (!video || !container) return;

    container.classList.remove('hidden');
    isScanning = true;
    hasDetectedBarcode = false;
    const scanId = ++activeScanId;
    reader = new BrowserMultiFormatReader();

    // Apply continuous autofocus after the stream starts (iOS requires applyConstraints, not getUserMedia hints)
    video.addEventListener('playing', async () => {
        const stream = video.srcObject;
        const [track] = stream?.getVideoTracks() ?? [];
        if (track) {
            try { await track.applyConstraints({ advanced: [{ focusMode: 'continuous' }] }); }
            catch (_) { /* focusMode not supported — silently ignored */ }
        }
    }, { once: true });

    try {
        const controls = await reader.decodeFromConstraints(
            {
                video: {
                    facingMode: { ideal: 'environment' },
                    width:  { ideal: 1920 },
                    height: { ideal: 1080 },
                },
            },
            video,
            (result, error, controls) => {
                if (result && isScanning && scanId === activeScanId && !hasDetectedBarcode) {
                    hasDetectedBarcode = true;
                    window.Livewire.dispatch('barcode-detected', { upc: result.getText() });
                    stopScanner(controls, scanId);
                }
                // NotFoundException fires every frame without a barcode — not a real error
                if (error && !(error instanceof NotFoundException)) {
                    console.error('Scanner error:', error);
                }
            }
        );

        if (!isScanning || scanId !== activeScanId || hasDetectedBarcode) {
            controls?.stop();
            return;
        }

        scannerControls = controls;
    } catch (err) {
        console.error('Could not start camera:', err);

        if (scanId === activeScanId) {
            resetScannerState();
        }

        // Surface the error in the UI if the page has an error element
        const errEl = document.getElementById('error-msg');
        if (errEl) {
            errEl.textContent = 'Could not access camera: ' + (err.message ?? err);
            errEl.style.display = 'block';
        }
    }
}

function resetScannerState() {
    scannerControls = null;
    reader = null;
    hasDetectedBarcode = false;
    isScanning = false;
    document.getElementById('scanner-container')?.classList.add('hidden');
}

function stopScanner(controls = scannerControls, scanId = activeScanId) {
    const activeControls = typeof controls?.stop === 'function'
        ? controls
        : scannerControls;

    activeControls?.stop();

    if (scanId === activeScanId) {
        activeScanId++;
        resetScannerState();
    }
}

window.addEventListener('toggle-scanner', () => {
    if (isScanning) {
        stopScanner();
    } else {
        startScanner();
    }
});

window.addEventListener('start-scanner', startScanner);
window.addEventListener('stop-scanner', stopScanner);

// Release camera when Livewire navigates away (SPA mode)
document.addEventListener('livewire:navigating', stopScanner);
