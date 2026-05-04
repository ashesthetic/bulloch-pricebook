<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Scan Barcode</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            background: #0f172a;
            color: #f1f5f9;
            font-family: system-ui, -apple-system, sans-serif;
            min-height: 100dvh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            text-align: center;
        }
        h1 { font-size: 1.25rem; font-weight: 600; margin-bottom: 0.5rem; }
        p { font-size: 0.875rem; color: #94a3b8; margin-bottom: 1.5rem; }
        #start-btn {
            padding: 0.85rem 2rem;
            font-size: 1rem;
            font-weight: 600;
            background: #2563eb;
            color: #fff;
            border: none;
            border-radius: 0.6rem;
            cursor: pointer;
            margin-bottom: 1rem;
        }
        #scanner-section { width: 100%; max-width: 48rem; }
        #scanner-container {
            width: 100%;
            height: min(72dvh, 34rem);
            min-height: 22rem;
            position: relative;
            border-radius: 0.75rem;
            overflow: hidden;
            background: #020617;
        }
        #scanner-video { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; display: block; }
        #scan-overlay {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            pointer-events: none;
        }
        #scan-guide {
            width: 88%;
            height: 24%;
            border: 2px solid rgba(255, 255, 255, 0.9);
            border-radius: 6px;
            box-shadow: 0 0 0 9999px rgba(0, 0, 0, 0.35);
        }
        .hint { margin-top: 0.75rem; font-size: 0.8rem; color: #64748b; margin-bottom: 0; }
        #success {
            display: none;
            background: #064e3b;
            border: 1px solid #059669;
            border-radius: 0.75rem;
            padding: 1.5rem;
            max-width: 340px;
            width: 100%;
        }
        #success h2 { font-size: 1.1rem; font-weight: 600; color: #34d399; margin-bottom: 0.5rem; }
        #success-name { color: #a7f3d0; font-size: 0.95rem; font-weight: 600; margin-bottom: 0; }
        #close-hint { color: #6ee7b7; font-size: 0.9rem; margin: 0; }
        #edit-btn {
            display: none;
            margin-top: 1rem;
            padding: 0.6rem 1.4rem;
            background: #059669;
            color: #fff;
            border-radius: 0.5rem;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.95rem;
        }
        #error-msg {
            display: none;
            background: #450a0a;
            border: 1px solid #dc2626;
            border-radius: 0.75rem;
            padding: 1rem;
            max-width: 340px;
            width: 100%;
            color: #fca5a5;
            font-size: 0.875rem;
        }
    </style>
</head>
<body>
    <div id="scanner-section">
        <h1>Scan Product Barcode</h1>
        <p>Point your camera at the product barcode.</p>

        <button id="start-btn" onclick="startCamera()">Start Scanning</button>

        <div id="scanner-container" style="display:none;">
            <video id="scanner-video" autoplay muted playsinline></video>
            <div id="scan-overlay"><div id="scan-guide"></div></div>
            <p class="hint">Align barcode inside the box.</p>
        </div>
    </div>

    <div id="success">
        <h2>Barcode Scanned</h2>
        <p id="success-name" style="display:none;"></p>
        <a id="edit-btn" href="#">Edit Product</a>
        <p id="close-hint">You can close this tab and return to the desktop.</p>
    </div>

    <div id="error-msg"></div>

    {{-- Livewire shim: barcode-scanner.js calls window.Livewire.dispatch() --}}
    <script>
        window.Livewire = {
            dispatch: function (event, data) {
                window.dispatchEvent(new CustomEvent(event, { detail: data }));
            }
        };

        window.addEventListener('barcode-detected', function (e) {
            const upc = e.detail?.upc ?? e.detail;
            if (!upc) return;

            fetch('/scan/{{ $token }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                },
                body: JSON.stringify({ upc }),
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('scanner-section').style.display = 'none';
                    document.getElementById('success').style.display = 'block';

                    if (data.product_name) {
                        const nameEl = document.getElementById('success-name');
                        nameEl.textContent = data.product_name;
                        nameEl.style.display = 'block';
                    }
                    if (data.edit_url) {
                        const btn = document.getElementById('edit-btn');
                        btn.href = data.edit_url;
                        btn.style.display = 'inline-block';
                        document.getElementById('close-hint').style.display = 'none';
                    }
                } else {
                    showError('Session expired. Please go back and try again.');
                }
            })
            .catch(() => showError('Network error. Please try again.'));
        });

        function showError(msg) {
            const el = document.getElementById('error-msg');
            el.textContent = msg;
            el.style.display = 'block';
        }

        function startCamera() {
            document.getElementById('start-btn').style.display = 'none';
            document.getElementById('scanner-container').style.display = 'block';
            window.dispatchEvent(new CustomEvent('toggle-scanner'));
        }
    </script>

    @vite('resources/js/barcode-scanner.js')
</body>
</html>
