@extends('layouts.app')
@section('title','Sancionar')

@section('content')
<style>
  /* Puedes pasar esto a tu layout */
  .page-wrap{ max-width: 980px; }
  .card-soft{ border:0; border-radius:14px; box-shadow: 0 6px 18px rgba(0,0,0,.06); }
  .muted-sm{ font-size:12px; color:#6c757d; }
  .mono{ font-variant-numeric: tabular-nums; }
  .res-item{ cursor:pointer; border-radius:12px; }
  .res-item:hover{ background:#f6f7fb; }
  .res-item.active{ background:#e9f2ff; border:1px solid rgba(13,110,253,.25); }
</style>

<div class="container py-3 page-wrap">
  <div class="mb-3">
    <h3 class="mb-1">SANCIONAR</h3>
    <p class="text-muted mb-0">Aplicar sanción a móviles activos.</p>
  </div>

  <div class="row">
    <div class="col-lg-7">
      <div class="card card-soft">
        <div class="card-body">

          <div class="d-flex align-items-start justify-content-between flex-wrap gap-2 mb-3">
            <h4 class="card-title mb-0">Aplicar sanción</h4>
            <a href="{{ route('operadora.sanciones.levantar_vista') }}" class="btn btn-outline-secondary btn-sm">
              Ir a Anular / Levantar
            </a>
          </div>

          {{-- BUSCADOR --}}
          <div class="mb-2">
            <label class="form-label fw-bold">Buscar móvil</label>
            <div class="input-group">
              <input type="text"
                     class="form-control"
                     id="qMovil"
                     placeholder="Ej: 17 / ABC123 / Juan / 123456"
                     autocomplete="off">
              <button class="btn btn-outline-secondary" type="button" id="btnLimpiarBusc">Limpiar</button>
            </div> 
            <div class="muted-sm mt-1">Busca móviles activos </div>
          </div>

          {{-- SELECCIONADO --}}
          <div class="mb-3" id="boxSeleccion" style="display:none;">
            <div class="alert alert-primary py-2 mb-0">
              <div class="d-flex justify-content-between align-items-center gap-2">
                <div>
                  <div class="fw-bold">Seleccionado:</div>
                  <div id="txtSeleccion" class="mono"></div>
                </div>
                <button type="button" class="btn btn-sm btn-outline-primary" id="btnQuitarSeleccion">Quitar</button>
              </div>
            </div>
          </div>

          {{-- RESULTADOS (lista) --}}
          <div class="mb-3">
            <div class="d-flex justify-content-between align-items-center">
              <label class="form-label fw-bold mb-1">Resultados</label>
              <small class="text-muted" id="txtEstadoResultados"></small>
            </div>

            <div class="border rounded p-2" style="max-height: 280px; overflow:auto;" id="listaResultados">
              <div class="text-muted text-center py-3">Escribe para buscar...</div>
            </div>
          </div>

          {{-- TIPOS --}}
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

        </div>
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
  const btnLimpiarBusc = document.getElementById('btnLimpiarBusc');

  const lista = document.getElementById('listaResultados');
  const txtEstado = document.getElementById('txtEstadoResultados');

  const boxSeleccion = document.getElementById('boxSeleccion');
  const txtSeleccion = document.getElementById('txtSeleccion');
  const btnQuitarSeleccion = document.getElementById('btnQuitarSeleccion');

  const selTipo = document.getElementById('selTipo');
  const btnSancionar = document.getElementById('btnSancionar');
  const msg = document.getElementById('msg');

  // 👇 guardamos selección real
  let seleccionado = null; // { mo_id, mo_taxi, placa, conduc_nombres, mo_conductor }
  let debounceT = null;

  // abort controller para evitar respuestas cruzadas
  let abortCtrl = null;

  function showMsg(html, cls='alert alert-info') {
    msg.innerHTML = `<div class="${cls} py-2 mb-0">${html}</div>`;
    setTimeout(()=>{ msg.innerHTML=''; }, 3500);
  }

  function esc(str){
    if(str === null || str === undefined) return '';
    return String(str)
      .replaceAll('&','&amp;')
      .replaceAll('<','&lt;')
      .replaceAll('>','&gt;')
      .replaceAll('"','&quot;')
      .replaceAll("'","&#039;");
  }

  function labelMovil(r){
    const placa = r.placa || 'SIN PLACA';
    const nom = r.conduc_nombres || 'SIN NOMBRE';
    const cc = r.mo_conductor || '';
    return `Móvil ${r.mo_taxi} | ${placa} | ${nom} | CC ${cc}`;
  }

  function setSeleccion(r){
    seleccionado = r;
    txtSeleccion.textContent = labelMovil(r);
    boxSeleccion.style.display = '';
    // resalta en lista
    document.querySelectorAll('[data-mo-id]').forEach(el=>{
      el.classList.toggle('active', String(el.getAttribute('data-mo-id')) === String(r.mo_id));
    });
  }

  function limpiarSeleccion(){
    seleccionado = null;
    boxSeleccion.style.display = 'none';
    txtSeleccion.textContent = '';
    document.querySelectorAll('[data-mo-id]').forEach(el=> el.classList.remove('active'));
  }

  btnQuitarSeleccion.addEventListener('click', limpiarSeleccion);

  btnLimpiarBusc.addEventListener('click', () => {
    qMovil.value = '';
    limpiarSeleccion();
    lista.innerHTML = `<div class="text-muted text-center py-3">Escribe para buscar...</div>`;
    txtEstado.textContent = '';
    qMovil.focus();
  });

  function pintarResultados(rows){
    if (!Array.isArray(rows)) {
      lista.innerHTML = `<div class="text-danger text-center py-3">Respuesta inválida del servidor.</div>`;
      return;
    }

    if (rows.length === 0) {
      lista.innerHTML = `<div class="text-muted text-center py-3">No hay coincidencias.</div>`;
      return;
    }

    let html = '';
    rows.forEach(r => {
      html += `
        <div class="p-2 res-item border mb-2 ${seleccionado && String(seleccionado.mo_id)===String(r.mo_id) ? 'active' : ''}"
             data-mo-id="${esc(r.mo_id)}">
          <div class="d-flex justify-content-between align-items-start gap-2">
            <div>
              <div class="fw-bold">Móvil <span class="mono">${esc(r.mo_taxi)}</span> — <span class="mono">${esc(r.placa || 'SIN PLACA')}</span></div>
              <div class="muted-sm">${esc(r.conduc_nombres || '')} <span class="mono">CC ${esc(r.mo_conductor || '')}</span></div>
            </div>
            <div>
              <button type="button" class="btn btn-sm btn-primary btnSel" data-mo-id="${esc(r.mo_id)}">Seleccionar</button>
            </div>
          </div>
        </div>
      `;
    });

    lista.innerHTML = html;

    // bind seleccionar
    lista.querySelectorAll('.btnSel').forEach(b => {
      b.addEventListener('click', function(e){
        e.preventDefault();
        const id = this.getAttribute('data-mo-id');
        const r = rows.find(x => String(x.mo_id) === String(id));
        if (r) setSeleccion(r);
      });
    });

    // click en toda la card
    lista.querySelectorAll('.res-item').forEach(card => {
      card.addEventListener('click', (ev) => {
        // evita doble click si se presiona botón
        if (ev.target.classList.contains('btnSel')) return;
        const id = card.getAttribute('data-mo-id');
        const r = rows.find(x => String(x.mo_id) === String(id));
        if (r) setSeleccion(r);
      });
    });
  }

  async function cargarMoviles(query=''){
    // abort previo
    try { abortCtrl?.abort(); } catch(_){}
    abortCtrl = new AbortController();

    txtEstado.textContent = query ? 'Buscando…' : 'Cargando…';
    lista.innerHTML = `<div class="text-muted text-center py-3">Cargando…</div>`;

    try{
      const res = await fetch(urlMoviles + '?q=' + encodeURIComponent(query), {
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept':'application/json' },
        signal: abortCtrl.signal
      });

      if (res.status === 419) throw new Error('Sesión expirada (419)');
      const rows = await res.json();

      txtEstado.textContent = `${Array.isArray(rows) ? rows.length : 0} resultado(s)`;
      pintarResultados(rows);
    } catch(e){
      if (e.name === 'AbortError') return; // normal al escribir rápido
      console.error(e);
      txtEstado.textContent = '';
      lista.innerHTML = `<div class="text-danger text-center py-3">Error cargando móviles.</div>`;
    }
  }

  qMovil.addEventListener('input', function(){
  clearTimeout(debounceT);
  const q = qMovil.value.trim();
  debounceT = setTimeout(() => cargarMoviles(q), 250);
});


  btnSancionar.addEventListener('click', async function(){
    const tpsa_id = selTipo.value;

    if (!seleccionado?.mo_id) return showMsg('Selecciona un móvil.', 'alert alert-warning');
    if (!tpsa_id) return showMsg('Selecciona un tipo de sanción.', 'alert alert-warning');

    btnSancionar.disabled = true;

    try{
      const res = await fetch(urlRegistrar, {
        method: 'POST',
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        },
        body: JSON.stringify({ mo_id: seleccionado.mo_id, tpsa_id })
      });

      const j = await res.json();
      if (!res.ok || !j.success) throw new Error(j.message || 'No se pudo registrar la sanción.');

      showMsg('✅ Sanción registrada correctamente.', 'alert alert-success');
      // opcional: limpiar selección y recargar lista
      // limpiarSeleccion();
      // cargarMoviles(qMovil.value.trim());
    } catch(e){
      showMsg('❌ ' + (e.message || 'Error'), 'alert alert-danger');
    } finally{
      btnSancionar.disabled = false;
    }
  });

  // carga inicial (sin query)
  cargarMoviles('');
});
</script>
@endsection
