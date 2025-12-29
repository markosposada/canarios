@extends('layouts.app') {{-- cambia por tu layout de operadora si aplica --}}
@section('title','Sancionar')

@section('content')
<div class="row mb-3">
  <div class="col-12">
    <h3 class="mb-1">SANCIONAR</h3>
    <p class="text-muted mb-0">Aplicar sanción a móviles activos.</p>
  </div>
</div>

<div class="row">
  <div class="col-lg-6 grid-margin stretch-card">
    <div class="card">
      <div class="card-body">
        <h4 class="card-title mb-3">Aplicar sanción</h4>

        <div class="mb-2">
          <label class="form-label fw-bold">Buscar por número de móvil</label>
          <input type="text" class="form-control" id="qMovil" inputmode="numeric" placeholder="Ej: 17">
          <small class="text-muted">Solo móviles activos (mo_estado=1).</small>
        </div>

        <div class="mb-3">
          <label class="form-label fw-bold">Selecciona móvil</label>
          <select class="form-select" id="selMovil">
            <option value="">-- busca arriba --</option>
          </select>
        </div>

        <div class="mb-3">
          <label class="form-label fw-bold">Tipo de sanción</label>
          <select class="form-select" id="selTipo">
            <option value="">-- seleccionar --</option>
            @foreach($tipos as $t)
              <option value="{{ $t->tpsa_id }}">
                {{ $t->tpsa_sancion }} ({{ $t->tpsa_horas }}h)
              </option>
            @endforeach
          </select>
        </div>

        <button class="btn btn-danger w-100" id="btnSancionar" type="button">Sancionar</button>

        <div id="msg" class="mt-3"></div>

        <hr>
        <a href="{{ route('operadora.sanciones.levantar_vista') }}" class="btn btn-outline-secondary w-100">
          Ir a Anular / Levantar sanciones
        </a>
      </div>
    </div>
  </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
  const urlMoviles   = "{{ route('operadora.sanciones.moviles_activos') }}";
  const urlRegistrar = "{{ route('operadora.sanciones.registrar') }}";

  const qMovil = document.getElementById('qMovil');
  const selMovil = document.getElementById('selMovil');
  const selTipo = document.getElementById('selTipo');
  const btnSancionar = document.getElementById('btnSancionar');
  const msg = document.getElementById('msg');

  let debounce = null;

  function showMsg(html, cls='alert alert-info') {
    msg.innerHTML = `<div class="${cls} py-2">${html}</div>`;
    setTimeout(()=>{ msg.innerHTML=''; }, 3500);
  }

  function cargarMoviles(query='') {
    fetch(urlMoviles + '?q=' + encodeURIComponent(query), {
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(rows => {
      selMovil.innerHTML = `<option value="">-- seleccionar --</option>`;
      if (!rows.length) {
        selMovil.innerHTML = `<option value="">No hay coincidencias</option>`;
        return;
      }
      rows.forEach(r => {
        const label = `Móvil ${r.mo_taxi} | ${r.placa || 'SIN PLACA'} | ${r.conduc_nombres} | CC ${r.mo_conductor}`;
        const opt = document.createElement('option');
        opt.value = r.mo_id;
        opt.textContent = label;
        selMovil.appendChild(opt);
      });
    })
    .catch(()=> {
      selMovil.innerHTML = `<option value="">Error cargando móviles</option>`;
    });
  }

  qMovil.addEventListener('input', function () {
    clearTimeout(debounce);
    debounce = setTimeout(() => cargarMoviles(qMovil.value.trim()), 250);
  });

  btnSancionar.addEventListener('click', function () {
    const mo_id = selMovil.value;
    const tpsa_id = selTipo.value;

    if (!mo_id) return showMsg('Selecciona un móvil.', 'alert alert-warning');
    if (!tpsa_id) return showMsg('Selecciona un tipo de sanción.', 'alert alert-warning');

    btnSancionar.disabled = true;

    fetch(urlRegistrar, {
      method: 'POST',
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
      },
      body: JSON.stringify({ mo_id, tpsa_id })
    })
    .then(r => r.json().then(j => ({ok:r.ok, j})))
    .then(({ok, j}) => {
      if (!ok || !j.success) throw new Error(j.message || 'No se pudo registrar la sanción.');
      showMsg('✅ Sanción registrada correctamente.', 'alert alert-success');
    })
    .catch(e => showMsg('❌ ' + (e.message || 'Error'), 'alert alert-danger'))
    .finally(() => btnSancionar.disabled = false);
  });

  cargarMoviles('');
});
</script>
@endsection
