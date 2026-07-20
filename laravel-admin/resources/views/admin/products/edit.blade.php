<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar {{ $product->name }} - Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Poppins', sans-serif; background: #f5f5f5; min-height: 100vh; }

        .admin-header {
            background: linear-gradient(135deg, #1a1a1a, #2d2d2d);
            padding: 20px 24px; display: flex; align-items: center; justify-content: space-between;
            box-shadow: 0 2px 10px rgba(0,0,0,0.3);
        }
        .admin-header-left { display: flex; align-items: center; gap: 14px; }
        .admin-header-left img { width: 45px; height: 45px; border-radius: 50%; border: 2px solid #FF6B35; }
        .admin-header-left h1 { color: #FFD700; font-size: 1.1rem; font-weight: 700; }
        .admin-header-left p { color: #aaa; font-size: 0.75rem; }
        .back-btn {
            background: transparent; color: #aaa; border: 2px solid #555;
            padding: 8px 16px; border-radius: 8px; font-family: 'Poppins', sans-serif;
            font-size: 0.8rem; font-weight: 600; cursor: pointer; transition: all 0.3s ease;
            text-decoration: none; display: inline-flex; align-items: center; gap: 6px;
        }
        .back-btn:hover { border-color: #FF6B35; color: #FF6B35; }

        .form-container { max-width: 600px; margin: 30px auto; padding: 0 20px 40px; }
        .form-card {
            background: white; border-radius: 16px; padding: 30px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06);
        }
        .form-card h2 {
            font-size: 1.2rem; font-weight: 700; color: #1a1a1a; margin-bottom: 24px;
            display: flex; align-items: center; gap: 10px;
        }
        .form-card h2 i { color: #FF6B35; }

        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-size: 0.85rem; font-weight: 600; color: #333; margin-bottom: 6px; }
        .form-group label .required { color: #e74c3c; }
        .form-control {
            width: 100%; padding: 12px 14px; border: 2px solid #e0e0e0; border-radius: 10px;
            font-family: 'Poppins', sans-serif; font-size: 0.9rem; color: #333;
            transition: border-color 0.3s ease; background: #fafafa;
        }
        .form-control:focus { outline: none; border-color: #FF6B35; background: white; box-shadow: 0 0 0 3px rgba(255,107,53,0.1); }
        textarea.form-control { resize: vertical; min-height: 80px; }
        select.form-control { cursor: pointer; appearance: auto; }

        .image-upload {
            border: 2px dashed #ddd; border-radius: 12px; padding: 30px;
            text-align: center; cursor: pointer; transition: all 0.3s ease; background: #fafafa;
        }
        .image-upload:hover { border-color: #FF6B35; background: #fff5f0; }
        .image-upload i { font-size: 2rem; color: #ccc; margin-bottom: 10px; }
        .image-upload p { color: #888; font-size: 0.85rem; }
        .image-upload input { display: none; }
        .image-upload .preview { max-width: 200px; max-height: 200px; border-radius: 10px; object-fit: cover; margin-top: 10px; display: none; }

        .current-image { margin-top: 10px; display: flex; align-items: center; gap: 12px; }
        .current-image img { width: 80px; height: 80px; border-radius: 10px; object-fit: cover; }
        .current-image span { font-size: 0.8rem; color: #888; }

        .checkbox-group { display: flex; align-items: center; gap: 10px; }
        .checkbox-group input[type="checkbox"] { width: 20px; height: 20px; accent-color: #FF6B35; cursor: pointer; }
        .checkbox-group label { margin-bottom: 0; cursor: pointer; }

        .form-actions { display: flex; gap: 12px; margin-top: 30px; }
        .btn {
            flex: 1; padding: 14px; border: none; border-radius: 10px;
            font-family: 'Poppins', sans-serif; font-size: 0.9rem; font-weight: 700;
            cursor: pointer; transition: all 0.3s ease; display: flex;
            align-items: center; justify-content: center; gap: 8px;
        }
        .btn-primary { background: linear-gradient(135deg, #FF6B35, #FF8C42); color: white; box-shadow: 0 4px 15px rgba(255,107,53,0.3); }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(255,107,53,0.4); }
        .btn-secondary { background: #e0e0e0; color: #555; }
        .btn-secondary:hover { background: #d0d0d0; }

        .error-text { color: #e74c3c; font-size: 0.75rem; margin-top: 4px; }
        .form-control.is-invalid { border-color: #e74c3c; }

        .toast {
            position: fixed; bottom: 30px; left: 50%; transform: translateX(-50%) translateY(100px);
            background: #1a1a1a; color: white; padding: 12px 24px; border-radius: 10px;
            font-size: 0.85rem; font-weight: 600; z-index: 1000; transition: transform 0.3s ease;
            box-shadow: 0 5px 20px rgba(0,0,0,0.3); display: flex; align-items: center; gap: 10px;
        }
        .toast.show { transform: translateX(-50%) translateY(0); }
        .toast.success { border-left: 4px solid #4CAF50; }
        .toast.error { border-left: 4px solid #e74c3c; }

        @media (max-width: 768px) {
            .admin-header { padding: 14px 16px; }
            .back-btn { padding: 7px 12px; font-size: 0.7rem; }
            .form-container { margin: 24px auto; padding: 0 16px 32px; }
        }
        @media (max-width: 576px) {
            .admin-header { padding: 12px 14px; flex-wrap: wrap; gap: 10px; }
            .admin-header-left { flex: 1; min-width: 0; }
            .admin-header-left img { width: 36px; height: 36px; }
            .admin-header-left h1 { font-size: 0.88rem; }
            .form-container { margin: 20px auto; padding: 0 14px 28px; }
            .form-card { padding: 22px; border-radius: 14px; }
            .form-card h2 { font-size: 1rem; margin-bottom: 18px; }
            .form-group label { font-size: 0.8rem; }
            .form-control { padding: 10px 12px; font-size: 0.85rem; }
            .image-upload { padding: 20px; }
        }
        @media (max-width: 420px) {
            .admin-header { padding: 10px 12px; }
            .admin-header-left img { width: 32px; height: 32px; }
            .admin-header-left h1 { font-size: 0.8rem; }
            .back-btn { padding: 6px 10px; font-size: 0.65rem; border-radius: 6px; }
            .form-card { padding: 18px; }
            .form-card h2 { font-size: 0.9rem; }
            .form-actions { flex-direction: column; gap: 10px; }
            .btn { padding: 12px; font-size: 0.85rem; }
        }
    </style>
</head>
<body>
    <div class="admin-header">
        <div class="admin-header-left">
            <img src="/img/logo.jpeg" alt="Logo"
                 onerror="this.style.background='#FF6B35'; this.style.borderRadius='50%'; this.src='data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 width=%2245%22 height=%2245%22><rect width=%2245%22 height=%2245%22 fill=%22%23FF6B35%22 rx=%2222%22/><text x=%2222.5%22 y=%2229%22 text-anchor=%22middle%22 fill=%22white%22 font-size=%2216%22 font-family=%22sans-serif%22 font-weight=%22bold%22>TT</text></svg>'">
            <div>
                <h1>Las Tortas Del Chiche</h1>
                <p>Editar: {{ $product->name }}</p>
            </div>
        </div>
        <a href="{{ route('admin.dashboard') }}" class="back-btn">
            <i class="fas fa-arrow-left"></i> Volver
        </a>
    </div>

    <div class="form-container">
        <div class="form-card">
            <h2><i class="fas fa-pencil-alt"></i> Editar Producto</h2>

            @if ($errors->any())
                <div style="background:#fff0f0; border:1px solid #ffcccc; border-radius:10px; padding:12px 16px; margin-bottom:20px;">
                    <p style="color:#e74c3c; font-size:0.85rem; font-weight:600;">
                        <i class="fas fa-exclamation-triangle"></i> Corrige los errores:
                    </p>
                    <ul style="color:#e74c3c; font-size:0.8rem; margin-top:6px; padding-left:20px;">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('admin.products.update', $product) }}" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <div class="form-group">
                    <label>Nombre <span class="required">*</span></label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                           value="{{ old('name', $product->name) }}" required maxlength="255">
                    @error('name') <div class="error-text">{{ $message }}</div> @enderror
                </div>

                <div class="form-group">
                    <label>Precio (MXN) <span class="required">*</span></label>
                    <input type="number" name="price" class="form-control @error('price') is-invalid @enderror"
                           value="{{ old('price', $product->price) }}" required min="0" step="0.50">
                    @error('price') <div class="error-text">{{ $message }}</div> @enderror
                </div>

                <div class="form-group">
                    <label>Categoría <span class="required">*</span></label>
                    <select name="category" class="form-control @error('category') is-invalid @enderror" required>
                        <option value="">Seleccionar...</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat }}" {{ old('category', $product->category) === $cat ? 'selected' : '' }}>
                                {{ ucfirst($cat) }}
                            </option>
                        @endforeach
                    </select>
                    @error('category') <div class="error-text">{{ $message }}</div> @enderror
                </div>

                <div class="form-group">
                    <label>Descripción</label>
                    <textarea name="description" class="form-control @error('description') is-invalid @enderror"
                              maxlength="500" rows="3">{{ old('description', $product->description) }}</textarea>
                    @error('description') <div class="error-text">{{ $message }}</div> @enderror
                </div>

                <div class="form-group">
                    <label>Orden</label>
                    <input type="number" name="sort_order" class="form-control @error('sort_order') is-invalid @enderror"
                           value="{{ old('sort_order', $product->sort_order) }}" min="0">
                    @error('sort_order') <div class="error-text">{{ $message }}</div> @enderror
                </div>

                <div class="form-group">
                    <label>Imagen</label>
                    @if($product->image)
                        <div class="current-image">
                            <img src="/{{ ltrim($product->image, '/') }}" alt="{{ $product->name }}">
                            <span>Imagen actual</span>
                        </div>
                    @endif
                    <div class="image-upload" id="dropZone" onclick="document.getElementById('imageInput').click()">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <p>Haz clic o arrastra una imagen nueva</p>
                        <p style="font-size:0.75rem; color:#aaa;">Solo 819×546px — JPG, PNG, WebP — Máx 2MB</p>
                        <input type="file" name="image" id="imageInput" accept="image/*"
                               onchange="previewImage(this)">
                        <img class="preview" id="imagePreview" alt="Preview">
                    </div>
                    @error('image') <div class="error-text">{{ $message }}</div> @enderror
                </div>

                <div class="form-group">
                    <div class="checkbox-group">
                        <input type="checkbox" name="active" id="activeCheck" value="1"
                               {{ old('active', $product->active) ? 'checked' : '' }}>
                        <label for="activeCheck">Producto activo (visible en el menú)</label>
                    </div>
                </div>

                <div class="form-group">
                    <label style="font-size:0.8rem;color:#888;margin-bottom:10px;">Opciones del producto</label>
                    <div style="display:flex;flex-wrap:wrap;gap:12px;">
                        <div class="checkbox-group">
                            <input type="checkbox" name="has_mojado" id="hasMojadoCheck" value="1"
                                   {{ old('has_mojado', $product->has_mojado) ? 'checked' : '' }}>
                            <label for="hasMojadoCheck">Mojado</label>
                        </div>
                        <div class="checkbox-group">
                            <input type="checkbox" name="has_seco" id="hasSecoCheck" value="1"
                                   {{ old('has_seco', $product->has_seco) ? 'checked' : '' }}>
                            <label for="hasSecoCheck">Seco</label>
                        </div>
                        <div class="checkbox-group">
                            <input type="checkbox" name="has_cochinita" id="hasCochinitaCheck" value="1"
                                   {{ old('has_cochinita', $product->has_cochinita) ? 'checked' : '' }}>
                            <label for="hasCochinitaCheck">Cochinita</label>
                        </div>
                        <div class="checkbox-group">
                            <input type="checkbox" name="has_lechon" id="hasLechonCheck" value="1"
                                   {{ old('has_lechon', $product->has_lechon) ? 'checked' : '' }}>
                            <label for="hasLechonCheck">Lechón</label>
                        </div>
                    </div>
                </div>

                <div class="form-actions">
                    <a href="{{ route('admin.dashboard') }}" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Guardar Cambios
                    </button>
                </div>
            </form>
        </div>
    </div>

    @if(session('success'))
        <div class="toast success show" id="toast">
            <i class="fas fa-check-circle"></i> {{ session('success') }}
        </div>
        <script>setTimeout(() => { document.getElementById('toast').classList.remove('show'); }, 3000);</script>
    @endif

    <script>
        window.imageValid = true;

        function validateImageDimensions(file) {
            return new Promise((resolve) => {
                if (!file) { resolve(true); return; }
                const img = new Image();
                img.onload = function() {
                    URL.revokeObjectURL(img.src);
                    resolve(img.width === 819 && img.height === 546);
                };
                img.onerror = function() { resolve(false); };
                img.src = URL.createObjectURL(file);
            });
        }

        function showImageError(msg) {
            let err = document.getElementById('imageDimError');
            if (!err) {
                err = document.createElement('div');
                err.id = 'imageDimError';
                err.style.cssText = 'color:#e74c3c; font-size:0.75rem; margin-top:6px; display:flex; align-items:center; gap:6px;';
                document.getElementById('dropZone').after(err);
            }
            err.innerHTML = '<i class="fas fa-exclamation-triangle"></i> ' + msg;
            err.style.display = 'flex';
        }

        function clearImageError() {
            const err = document.getElementById('imageDimError');
            if (err) err.style.display = 'none';
        }

        async function previewImage(input) {
            const preview = document.getElementById('imagePreview');
            if (input.files && input.files[0]) {
                const file = input.files[0];
                const valid = await validateImageDimensions(file);
                if (!valid) {
                    const img = new Image();
                    img.onload = function() {
                        showImageError('La imagen debe ser de 819×546 píxeles. Tu imagen tiene ' + img.width + '×' + img.height);
                        URL.revokeObjectURL(img.src);
                    };
                    img.src = URL.createObjectURL(file);
                    preview.style.display = 'none';
                    window.imageValid = false;
                    input.value = '';
                    return;
                }
                clearImageError();
                window.imageValid = true;
                const reader = new FileReader();
                reader.onload = function(e) { preview.src = e.target.result; preview.style.display = 'block'; };
                reader.readAsDataURL(file);
            }
        }

        document.querySelector('form').addEventListener('submit', function(e) {
            if (!window.imageValid) {
                e.preventDefault();
                showToast('La imagen no tiene las dimensiones correctas (819×546)', 'error');
            }
        });

        function showToast(message, type) {
            let toast = document.getElementById('formToast');
            if (!toast) {
                toast = document.createElement('div');
                toast.id = 'formToast';
                toast.style.cssText = 'position:fixed;bottom:30px;left:50%;transform:translateX(-50%) translateY(100px);background:#1a1a1a;color:white;padding:12px 24px;border-radius:10px;font-size:0.85rem;font-weight:600;z-index:1000;transition:transform 0.3s ease;box-shadow:0 5px 20px rgba(0,0,0,0.3);display:flex;align-items:center;gap:10px;max-width:90%;';
                document.body.appendChild(toast);
            }
            const icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';
            const border = type === 'success' ? '#4CAF50' : '#e74c3c';
            toast.innerHTML = '<i class="fas ' + icon + '"></i> ' + message;
            toast.style.borderLeft = '4px solid ' + border;
            toast.style.transform = 'translateX(-50%) translateY(0)';
            setTimeout(() => { toast.style.transform = 'translateX(-50%) translateY(100px)'; }, 3000);
        }

        const dropZone = document.getElementById('dropZone');
        dropZone.addEventListener('dragover', (e) => { e.preventDefault(); dropZone.style.borderColor = '#FF6B35'; });
        dropZone.addEventListener('dragleave', () => { dropZone.style.borderColor = '#ddd'; });
        dropZone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropZone.style.borderColor = '#ddd';
            const input = document.getElementById('imageInput');
            input.files = e.dataTransfer.files;
            previewImage(input);
        });
    </script>
</body>
</html>
