{{-- resources/views/servicios/consulta-token.blade.php --}}
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Los Canarios | Estado de tu servicio</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

  <style>
    :root{
      --brand1:#0d6efd;
      --brand2:#6610f2;
      --soft:#f6f7fb;
      --muted:#6c757d;
      --ink:#0f172a;
    }

    body{ background: var(--soft); color: var(--ink); }

    /* ================= HERO ================= */
    .hero{
      background:
        radial-gradient(1100px 420px at 15% 0%, rgba(13,110,253,.18), transparent 60%),
        radial-gradient(900px 380px at 85% 10%, rgba(102,16,242,.16), transparent 55%),
        linear-gradient(135deg, rgba(13,110,253,.12), rgba(102,16,242,.10));
      border-bottom: 1px solid rgba(0,0,0,.06);
    }

    .brand-wrap{
      display:flex;
      align-items:center;
      gap:20px;
    }

    /* 🔥 LOGO GRANDE Y VISTOSO */
    .brand-logo{
      width: 110px;
      height: 110px;
      object-fit: contain;
      border-radius: 22px;
      background: rgba(255,255,255,.9);
      border: 1px solid rgba(0,0,0,.08);
      box-shadow: 0 18px 40px rgba(0,0,0,.18);
      padding: 12px;
      transition: transform .25s ease;
    }
    .brand-logo:hover{ transform: scale(1.05); }

    .brand-name{
      font-weight: 900;
      font-size: 2rem;
      letter-spacing: -.02em;
      margin: 0;
      line-height: 1.05;
    }

    .brand-sub{ margin:0; color: var(--muted); }

    .pill{
      display:inline-flex;
      align-items:center;
      gap:.45rem;
      border-radius: 999px;
      padding:.35rem .8rem;
      font-weight: 700;
      border: 1px solid rgba(0,0,0,.08);
      background: rgba(255,255,255,.75);
      font-size:.85rem;
    }

    /* ================= CARD ================= */
    .card-pro{
      border: 0;
      border-radius: 20px;
      box-shadow: 0 14px 34px rgba(0,0,0,.10);
      overflow: hidden;
    }

    .card-pro .card-header{
      background: rgba(255,255,255,.75);
      backdrop-filter: blur(6px);
      border-bottom: 1px solid rgba(0,0,0,.06);
    }

    .token-chip{
      display:inline-flex;
      align-items:center;
      justify-content:center;
      padding:.5rem 1rem;
      border-radius: 999px;
      background:#111827;
      color:#fff;
      font-weight: 900;
      letter-spacing:.2rem;
      font-size: 1rem;
      user-select:none;
    }

    .kv{
      display:flex;
      justify-content:space-between;
      gap: 1rem;
      padding: .75rem 0;
      border-bottom: 1px dashed rgba(0,0,0,.10);
      font-size: .95rem;
    }
    .kv:last-child{ border-bottom:0; }
    .k{ color: var(--muted); font-weight: 700; }
    .v{ font-weight: 800; text-align:right; }

    .btn-soft{
      border-radius: 12px;
      border: 1px solid rgba(0,0,0,.10);
      background: #fff;
      font-weight: 700;
    }

    .skeleton{
      border-radius: 12px;
      background: linear-gradient(90deg, rgba(0,0,0,.06), rgba(0,0,0,.10), rgba(0,0,0,.06));
      background-size: 200% 100%;
      animation: shimmer 1.1s infinite;
    }
    @keyframes shimmer { 0%{background-position: 200% 0;} 100%{background-position: -200% 0;} }

    /* ================= RESPONSIVE ================= */
    @media (max-width: 768px){
      .brand-wrap{
        flex-direction: column;
        text-align: center;
      }

      .brand-logo{
        width: 85px;
        height: 85px;
        border-radius: 18px;
        padding: 10px;
      }

      .brand-name{ font-size: 1.6rem; }

      .token-chip{
        font-size: .9rem;
        letter-spacing:.15rem;
      }

      .kv{
        flex-direction: column;
        align-items: flex-start;
      }
      .v{ text-align:left; }
    }
  </style>
</head>

<body>

  {{-- HERO --}}
  <section class="hero py-4 py-md-5">
    <div class="container" style="max-width: 980px;">
      <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-3">

        <div class="brand-wrap">
          <img src="{{ asset('images/canarios.png') }}" alt="Logo Los Canarios" class="brand-logo">
          <div>
            <h1 class="brand-name">Los Canarios</h1>
            <p class="brand-sub">Estado de tu servicio de taxi</p>

            <div class="mt-2 d-flex flex-wrap gap-2 justify-content-md-start justify-content-center">
              <span class="pill">🛡️ Seguro</span>
              <span class="pill">⚡ Rápido</span>
              <span class="pill">📍 Confiable</span>
            </div>
          </div>
        </div>

        <div class="text-md-end text-center">
          <div class="text-secondary small mb-1">Código de seguimiento</div>
          <div id="tokenChip" class="token-chip">—</div>
          <div class="text-muted mt-2 small" id="estadoLabel">Preparando consulta…</div>
        </div>

      </div>
    </div>
  </section>

  {{-- CONTENT --}}
  <main class="container py-4 py-md-5" style="max-width: 980px;">
    <div class="row g-4">

      <div class="col-lg-7">
        <div class="card card-pro">
          <div class="card-header py-3 px-4 d-flex justify-content-between">
            <div class="fw-bold">Detalles de tu servicio</div>
            <div class="small text-muted">Actualizado automáticamente</div>
          </div>

          <div class="card-body p-4">
            <div id="resultado">
              {{-- Skeleton inicial --}}
              <div class="mb-3 skeleton" style="height: 18px; width: 55%;"></div>
              <div class="skeleton mb-2" style="height: 14px; width: 92%;"></div>
              <div class="skeleton mb-2" style="height: 14px; width: 86%;"></div>
              <div class="skeleton mb-2" style="height: 14px; width: 88%;"></div>
              <div class="skeleton mb-2" style="height: 14px; width: 80%;"></div>
              <div class="skeleton" style="height: 14px; width: 68%;"></div>
            </div>

            <div class="d-flex flex-wrap gap-2 mt-4 justify-content-center justify-content-md-start">
              <button class="btn btn-soft px-3" id="btnReintentar" type="button" style="display:none;">
                Reintentar
              </button>
              <button class="btn btn-dark px-3" type="button" onclick="window.location.reload()">
                Actualizar
              </button>
            </div>
          </div>
        </div>
      </div>

      {{-- (Opcional) columna derecha, si la quieres --}}
      <div class="col-lg-5">
        <div class="card card-pro">
          <div class="card-header py-3 px-4">
            <div class="fw-bold">Atención</div>
          </div>
          <div class="card-body p-4 text-secondary">
           Si el servicio es fuera del perimetro urbano, la tarifa es de comun acuerdo con el conductor.
          </div>
        </div>
      </div>

    </div>
  </main>

  <script>
    function setEstado(texto) {
      const el = document.getElementById('estadoLabel');
      if (el) el.textContent = texto;
    }

    function showRetry(show) {
      const btn = document.getElementById('btnReintentar');
      if (btn) btn.style.display = show ? 'inline-flex' : 'none';
    }

    function escapeHtml(str) {
      return String(str ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
    }

    function renderAlert(message, type='info') {
      const r = document.getElementById('resultado');
      r.innerHTML = `
        <div class="alert alert-${type} mb-0">
          ${escapeHtml(message)}
        </div>
      `;
    }

    function renderResultadoSeguro(data) {
      if (!data || !data.found) {
        const msg = (data && data.message) ? data.message : 'No encontramos un servicio con este código.';
        setEstado('Sin resultados');
        showRetry(true);
        return renderAlert(msg, 'warning');
      }

      setEstado('Servicio encontrado');
      showRetry(false);

      const valorFmt = (data.valor == null)
        ? '—'
        : new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP' }).format(Number(data.valor));

      const r = document.getElementById('resultado');

      r.innerHTML = `
        <div class="mb-3">
          <div class="text-secondary small">Resumen</div>
          <div class="fw-bold">Tu solicitud está registrada ✅</div>
          <div class="text-muted small mt-1">Verifica los datos del vehículo antes de abordar.</div>
        </div>

        <div class="kv"><div class="k">Conductor</div><div class="v">${escapeHtml(data.conductor ?? '—')}</div></div>
        <div class="kv"><div class="k">Móvil</div><div class="v">${escapeHtml(data.movil ?? '—')}</div></div>
        <div class="kv"><div class="k">Placa</div><div class="v">${escapeHtml(data.placa ?? '—')}</div></div>
        <div class="kv"><div class="k">Dirección</div><div class="v">${escapeHtml(data.direccion ?? '—')}</div></div>
        <div class="kv"><div class="k">Fecha</div><div class="v">${escapeHtml(data.fecha ?? '—')}</div></div>
        <div class="kv"><div class="k">Hora</div><div class="v">${escapeHtml(data.hora ?? '—')}</div></div>
        <div class="kv"><div class="k">Valor sugerido</div><div class="v">${escapeHtml(valorFmt)}</div></div>
      `;
    }

    async function consultar(token) {
      try {
        setEstado('Consultando…');
        showRetry(false);

        const url = `{{ route('servicios.consulta.buscar') }}?token=${encodeURIComponent(token)}`;
        const res = await fetch(url, { method: 'GET', headers: { 'Accept': 'application/json' } });

        let data = null;
        try { data = await res.json(); } catch (_e) {}

        if (!res.ok) {
          setEstado('Error');
          showRetry(true);
          return renderAlert((data && data.message) ? data.message : 'Error consultando el código.', 'danger');
        }

        renderResultadoSeguro(data);
      } catch (e) {
        setEstado('Error de red');
        showRetry(true);
        renderAlert('Error de red al consultar el código.', 'danger');
      }
    }

    document.addEventListener('DOMContentLoaded', function () {
      const params = new URLSearchParams(window.location.search);
      const tokenUrl = (params.get('token') || '').toUpperCase().trim();

      document.getElementById('tokenChip').textContent = tokenUrl || '—';

      // ✅ Validación correcta: 1 letra + 2 números
      if (!/^[A-Z]\d{2}$/.test(tokenUrl)) {
        setEstado('Código inválido');
        showRetry(false);
        return renderAlert('El enlace no contiene un código válido. Debe ser 1 letra y 2 números (ej: A07).', 'warning');
      }

      document.getElementById('btnReintentar').addEventListener('click', () => consultar(tokenUrl));

      // ✅ Consulta automática
      consultar(tokenUrl);
    });
  </script>

</body>
</html>
