import { BrowserMultiFormatReader } from '@zxing/browser';
import { BarcodeFormat, DecodeHintType, NotFoundException } from '@zxing/library';

let reader = null;
let scannerControls = null;
let isScanning = false;
let hasDetectedBarcode = false;
let activeScanId = 0;

const hints = new Map();
hints.set(DecodeHintType.POSSIBLE_FORMATS, [
    BarcodeFormat.UPC_A,
    BarcodeFormat.UPC_E,
    BarcodeFormat.EAN_13,
    BarcodeFormat.EAN_8,
]);
hints.set(DecodeHintType.TRY_HARDER, true);

const scannerOptions = {
    delayBetweenScanAttempts: 100,
    delayBetweenScanSuccess: 250,
};

const cameraConstraints = {
    video: {
        facingMode: { ideal: 'environment' },
        width:  { min: 1280, ideal: 1920 },
        height: { min: 720, ideal: 1080 },
    },
};

const fallbackCameraConstraints = {
    video: {
        facingMode: { ideal: 'environment' },
        width:  { ideal: 1280 },
        height: { ideal: 720 },
    },
};

async function startScanner() {
    if (isScanning) return;

    const video = document.getElementById('scanner-video');
    const container = document.getElementById('scanner-container');
    if (!video || !container) return;

    container.classList.remove('hidden');
    isScanning = true;
    hasDetectedBarcode = false;
    const scanId = ++activeScanId;
    reader = new BrowserMultiFormatReader(hints, scannerOptions);

    // Apply continuous autofocus after the stream starts (iOS requires applyConstraints, not getUserMedia hints)
    video.addEventListener('playing', async () => {
        const stream = video.srcObject;
        const [track] = stream?.getVideoTracks() ?? [];
        if (track) {
            try {
                await track.applyConstraints({
                    advanced: [
                        { focusMode: 'continuous' },
                        { exposureMode: 'continuous' },
                    ],
                });
            } catch (_) { /* Camera constraint not supported — silently ignored */ }
        }
    }, { once: true });

    try {
        const controls = await startDecoding(video, scanId);

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

async function startDecoding(video, scanId) {
    const onFrameDecoded = (result, error, controls) => {
        if (result && isScanning && scanId === activeScanId && !hasDetectedBarcode) {
            hasDetectedBarcode = true;
            window.Livewire.dispatch('barcode-detected', { upc: result.getText() });
            stopScanner(controls, scanId);
        }
        // NotFoundException fires every frame without a barcode — not a real error
        if (error && !(error instanceof NotFoundException)) {
            console.error('Scanner error:', error);
        }
    };

    try {
        return await reader.decodeFromConstraints(cameraConstraints, video, onFrameDecoded);
    } catch (err) {
        if (err?.name !== 'OverconstrainedError' && err?.name !== 'ConstraintNotSatisfiedError') {
            throw err;
        }

        return reader.decodeFromConstraints(fallbackCameraConstraints, video, onFrameDecoded);
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
