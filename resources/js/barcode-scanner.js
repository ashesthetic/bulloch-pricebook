import { BrowserMultiFormatReader } from '@zxing/browser';
import { NotFoundException } from '@zxing/library';

const reader = new BrowserMultiFormatReader();
let isScanning = false;

async function startScanner() {
    if (isScanning) return;

    const video = document.getElementById('scanner-video');
    const container = document.getElementById('scanner-container');
    if (!video || !container) return;

    container.classList.remove('hidden');
    isScanning = true;

    try {
        await reader.decodeFromConstraints(
            { video: { facingMode: 'environment' } },
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
    }
}

function stopScanner() {
    reader.reset();
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

window.addEventListener('stop-scanner', stopScanner);

// Release camera when Livewire navigates away (SPA mode)
document.addEventListener('livewire:navigating', stopScanner);
