// Potwierdzenie przed usunięciem (PU-10)
document.querySelectorAll('form[data-confirm]').forEach(f =>
  f.addEventListener('submit', e => { if (!confirm(f.dataset.confirm)) e.preventDefault(); }));
// Zamykanie komunikatów
document.querySelectorAll('.flash.ok').forEach(el => setTimeout(() => el.remove(), 4000));
