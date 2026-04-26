import { BrowserMultiFormatReader } from '@zxing/browser';
import { NotFoundException } from '@zxing/library';

let reader = null;
let isScanning = false;

async function startScanner() {
    if (isScanning) return;

    const video = document.getElementById('scanner-video');
    const container = document.getElementById('scanner-container');
    if (!video || !container) return;

    container.classList.remove('hidden');
    isScanning = true;
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
        await reader.decodeFromConstraints(
            {
                video: {
                    facingMode: { ideal: 'environment' },
                    width:  { ideal: 1920 },
                    height: { ideal: 1080 },
                },
            },
            video,
            (result, error) => {
                if (result) {
                    window.Livewire.dispatch('barcode-detected', { upc: result.getText() });
                    stopScanner();
                }
                // NotFoundException fires every frame without a barcode — not a real error
                if (error && !(error instanceof NotFoundException)) {
                    console.error('Scanner error:', error);
                }
            }
        );
    } catch (err) {
        console.error('Could not start camera:', err);
        isScanning = false;
        document.getElementById('scanner-container')?.classList.add('hidden');
        // Surface the error in the UI if the page has an error element
        const errEl = document.getElementById('error-msg');
        if (errEl) {
            errEl.textContent = 'Could not access camera: ' + (err.message ?? err);
            errEl.style.display = 'block';
        }
    }
}

function stopScanner() {
    reader?.reset();
    reader = null;
    isScanning = false;
    document.getElementById('scanner-container')?.classList.add('hidden');
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
