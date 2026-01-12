<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Registro de taxistas</title>

    {{-- Bootstrap 5 (CDN) --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body{
            min-height: 100vh;
            background: radial-gradient(1200px 600px at 10% 10%, #e8f0ff 0%, transparent 60%),
                        radial-gradient(900px 500px at 90% 20%, #ffe9f1 0%, transparent 55%),
                        #f6f7fb;
        }
        .page-wrap{
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .brand-badge{
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            font-weight: 600;
            letter-spacing: .2px;
        }
        .card{
            border: 0;
            box-shadow: 0 18px 45px rgba(17, 24, 39, .12);
            border-radius: 18px;
            overflow: hidden;
        }
        .card-header{
  /* Verdes tipo logo */
  background: linear-gradient(135deg, #0B5A3A 0%, #0E7A4A 55%, #0A4E34 100%); /* verdes [file:142] */
  color: #fff;
  border: 0;
  padding: 1.25rem 1.25rem;

  /* Acento amarillo/dorado como el borde del logo */
  border-bottom: 4px solid #F2C94C; /* amarillo/dorado [file:142] */

  /* Mejor contraste del texto sobre verde */
  text-shadow: 0 1px 2px rgba(0,0,0,.25);
}

        .req{ color: #dc3545; font-weight: 700; }
        .form-control, .btn{
            border-radius: 12px;
        }
        .form-control:focus{
            box-shadow: 0 0 0 .25rem rgba(99, 102, 241, .18);
        }
        .help{
            color: #6c757d;
            font-size: .875rem;
        }
    </style>
</head>
<body>
<div class="page-wrap py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 col-lg-9 col-xl-8">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                            <div>
                                <div class="brand-badge">
                                    Canarios
                                    <span class="badge text-bg-light">Taxistas</span>
                                </div>
                                <h1 class="h4 mb-0 mt-2">Registro de taxistas</h1>
                                <div class="small opacity-75">Los campos con <span class="req">*</span> son obligatorios.</div>
                            </div>
                            <div class="text-end small opacity-75">
                                Espinal, Tolima
                            </div>
                        </div>
                    </div>

                    <div class="card-body p-4 p-md-5">
                        @if (session('ok'))
                            <div class="alert alert-success">
                                {{ session('ok') }}
                            </div>
                        @endif

                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <div class="fw-semibold mb-1">Revisa los datos:</div>
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <form method="POST" action="{{ route('taxistas.store') }}" class="row g-3">
                            @csrf

                            <div class="col-12 col-md-6">
                                <label for="nombre" class="form-label">Nombre <span class="req">*</span></label>
                                <input
                                    type="text"
                                    id="nombre"
                                    name="nombre"
                                    value="{{ old('nombre') }}"
                                    class="form-control @error('nombre') is-invalid @enderror"
                                    required
                                    autocomplete="off"
                                >
                                @error('nombre')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="apellidos" class="form-label">Apellidos <span class="req">*</span></label>
                                <input
                                    type="text"
                                    id="apellidos"
                                    name="apellidos"
                                    value="{{ old('apellidos') }}"
                                    class="form-control @error('apellidos') is-invalid @enderror"
                                    required
                                    autocomplete="off"
                                >
                                @error('apellidos')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="cedula" class="form-label">Cédula <span class="req">*</span></label>
                              <input type="text" id="cedula" name="cedula"
       value="{{ old('cedula') }}"
       class="form-control @error('cedula') is-invalid @enderror"
       required inputmode="numeric" pattern="\d+"
       maxlength="30"
       oninput="this.value=this.value.replace(/\D/g,'')">

                                <div class="help mt-1">Sin puntos ni espacios (recomendado).</div>
                                @error('cedula')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="celular" class="form-label">Celular <span class="req">*</span></label>
                                <input type="text" id="celular" name="celular"
       value="{{ old('celular') }}"
       class="form-control @error('celular') is-invalid @enderror"
       required inputmode="numeric" pattern="\d{10}"
       maxlength="10"
       oninput="this.value=this.value.replace(/\D/g,'').slice(0,10)">
                                @error('celular')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="movil" class="form-label">Móvil (número de taxi) <span class="req">*</span></label>
<input type="text" id="movil" name="movil"
       value="{{ old('movil') }}"
       class="form-control @error('movil') is-invalid @enderror"
       required inputmode="numeric"
       maxlength="3"
       pattern="\d{1,3}"
       oninput="this.value=this.value.replace(/\D/g,'').slice(0,3)">

                                @error('movil')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="placa_taxi" class="form-label">Placa del taxi <span class="req">*</span></label>
                                <input type="text" id="placa_taxi" name="placa_taxi"
       value="{{ old('placa_taxi') }}"
       class="form-control @error('placa_taxi') is-invalid @enderror"
       required pattern="[A-Za-z]{3}[0-9]{3}" maxlength="6"
       style="text-transform: uppercase"
       oninput="this.value=this.value.replace(/[^a-zA-Z0-9]/g,'').toUpperCase().slice(0,6)">
                                <div class="help mt-1">Ej: ABC123 </div>
                                @error('placa_taxi')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12">
                                <label for="correo_electronico" class="form-label">Correo electrónico (opcional)</label>
                                <input
                                    type="email"
                                    id="correo_electronico"
                                    name="correo_electronico"
                                    value="{{ old('correo_electronico') }}"
                                    class="form-control @error('correo_electronico') is-invalid @enderror"
                                    placeholder="taxista@correo.com"
                                    autocomplete="off"
                                >
                                <div class="help mt-1">Si no tiene correo, déjalo vacío.</div>
                                @error('correo_electronico')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12 d-flex flex-wrap gap-2 justify-content-end pt-2">
                                <a href="{{ url('/') }}" class="btn btn-outline-secondary px-4">Cancelar</a>
                                <button type="submit" class="btn btn-primary px-4">Guardar</button>
                            </div>
                        </form>
                    </div>

                    <div class="card-footer bg-white border-0 px-4 px-md-5 pb-4">
                        <div class="small text-muted">
                          
                        </div>
                    </div>
                </div>

                <div class="text-center mt-3 small text-muted">
                    © {{ date('Y') }} Los Canarios
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
