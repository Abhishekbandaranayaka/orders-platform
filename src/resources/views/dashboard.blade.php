<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Orders Platform — Dashboard</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="api-token" content="{{ $apiToken }}">
  <style>
    body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif;margin:0;background:#0b1020;color:#e7ecf1}
    .wrap{max-width:1080px;margin:40px auto;padding:0 16px}
    .grid{display:grid;gap:16px;grid-template-columns:repeat(auto-fit,minmax(220px,1fr))}
    .card{background:#10162e;border:1px solid #1f2a4a;border-radius:14px;padding:16px;box-shadow:0 10px 30px rgba(0,0,0,.25)}
    .title{font-size:28px;margin:0 0 12px}
    .muted{opacity:.75}
    .kpi{font-size:32px;font-weight:700}
    table{width:100%;border-collapse:collapse}
    th,td{padding:10px;border-bottom:1px solid #1f2a4a;text-align:left}
    a{color:#7cc4ff;text-decoration:none}
    a:hover{text-decoration:underline}
    .row{display:flex;gap:12px;flex-wrap:wrap;align-items:center}
    .btn{display:inline-block;padding:8px 12px;border-radius:10px;background:#172041;border:1px solid #2f3d6e;color:#e7ecf1}
    .btn:hover{background:#1a2550}
    .small{font-size:12px}
  </style>
</head>
<body>
  <div class="wrap">
    <h1 class="title">Orders Platform — Dashboard</h1>

    <div class="row" style="margin-bottom:16px">
      <a class="btn" href="/horizon" target="_blank">Open Horizon</a>
      <a class="btn" href="/api/kpi/today" target="_blank">GET /api/kpi/today</a>
      <a class="btn" href="/api/leaderboard/top?limit=10" target="_blank">GET /api/leaderboard/top</a>
      <span class="small muted">Auto-refresh every 5s</span>
    </div>

    <div class="grid" style="margin-bottom:16px">
      <div class="card">
        <div class="muted">Revenue (today)</div>
        <div id="rev" class="kpi">—</div>
      </div>
      <div class="card">
        <div class="muted">Orders (today)</div>
        <div id="cnt" class="kpi">—</div>
      </div>
      <div class="card">
        <div class="muted">AOV (today)</div>
        <div id="aov" class="kpi">—</div>
      </div>
    </div>

    <div class="card">
      <div class="row" style="justify-content:space-between">
        <h2 style="margin:0">Top Customers</h2>
        <div class="muted small">Ranking by spend (cents)</div>
      </div>
      <div style="overflow:auto">
        <table>
          <thead>
            <tr><th>#</th><th>Customer ID</th><th>Spend (¢)</th></tr>
          </thead>
          <tbody id="rows"></tbody>
        </table>
      </div>
    </div>

    <p class="muted small" style="margin-top:24px">
      Tip: Import a CSV with <code>new</code> external IDs to see live changes:
      <code>POST /api/orders/import</code> (file upload) or <code>{"path":"orders_today.csv"}</code>.
    </p>
  </div>

  <script>
    const t = document.querySelector('meta[name="api-token"]').content || '';
    const hdrs = {'X-Api-Key': t};

    function fmtCents(c){ return (c/100).toLocaleString(undefined, {style:'currency', currency:'USD'}); }

    async function refresh() {
      try {
        const [kpiRes, lbRes] = await Promise.all([
          fetch('/api/kpi/today', {headers: hdrs}),
          fetch('/api/leaderboard/top?limit=10', {headers: hdrs})
        ]);

        const kpi = await kpiRes.json();
        const top = await lbRes.json();

        document.getElementById('rev').textContent = fmtCents(kpi.revenue_cents||0);
        document.getElementById('cnt').textContent = (kpi.order_count||0);
        document.getElementById('aov').textContent = fmtCents(kpi.aov_cents||0);

        const tbody = document.getElementById('rows');
        tbody.innerHTML = '';
        if (Array.isArray(top) && top.length) {
          top.forEach((r, idx) => {
            const tr = document.createElement('tr');
            tr.innerHTML = `<td>${idx+1}</td><td>${r.customer_id}</td><td>${r.spend_cents}</td>`;
            tbody.appendChild(tr);
          });
        } else {
          const tr = document.createElement('tr');
          tr.innerHTML = `<td colspan="3" class="muted">No data yet</td>`;
          tbody.appendChild(tr);
        }
      } catch (e) {
        console.error(e);
      }
    }

    refresh();
    setInterval(refresh, 5000);
  </script>
</body>
</html>
