(function(){
  function qs(sel, ctx){ return (ctx||document).querySelector(sel); }
  function qsa(sel, ctx){ return Array.from((ctx||document).querySelectorAll(sel)); }

  const app = qs('#categories-app');
  if (!app) return;

  const listUrl = app.dataset.listUrl;
  const createUrl = app.dataset.createUrl;
  const updateUrl = app.dataset.updateUrl;
  const deleteUrl = app.dataset.deleteUrl;

  function el(tag, props={}, children=[]){
    const e = document.createElement(tag);
    for(const k in props){ if(k==='class') e.className = props[k]; else e.setAttribute(k, props[k]); }
    (Array.isArray(children)?children:[children]).forEach(c=>{ if(typeof c==='string') e.appendChild(document.createTextNode(c)); else if(c) e.appendChild(c); });
    return e;
  }

  function renderCategories(items){
    app.innerHTML = '';
    const grid = el('div',{class:'card-grid'});
    items.forEach((c, idx)=>{
      const card = el('div',{class:'card fade-in', 'data-cat-id': c.id});
      // stagger entrance per-item
      card.style.animationDelay = (idx * 70) + 'ms';
      card.appendChild(el('h3',{}, c.name));
      card.appendChild(el('p',{class:'muted'}, 'Slug: ' + c.slug));
      const controls = el('div', {style:'margin-top:12px;display:flex;gap:8px;'});
      const edit = el('button',{class:'button button-secondary', type:'button', 'data-id': c.id}, 'Edit');
      const del = el('button',{class:'button button-danger', type:'button', 'data-id': c.id}, 'Delete');
      edit.addEventListener('click', ()=> openEditModal(c));
      del.addEventListener('click', ()=> onDelete(c.id));
      controls.appendChild(edit); controls.appendChild(del);
      card.appendChild(controls);
      grid.appendChild(card);
      // animate in (staggered)
      requestAnimationFrame(()=> card.classList.add('slide-up'));
    });
    app.appendChild(grid);
  }

  function fetchJson(url, opts){
    const headers = Object.assign({'Accept':'application/json'}, (opts && opts.headers) || {});
    if (window.CK_CSRF_TOKEN) { headers['X-CSRF-Token'] = window.CK_CSRF_TOKEN; }
    return fetch(url, Object.assign({credentials:'same-origin', headers: headers}, opts)).then(async r=>{
      let parsed = {};
      try { parsed = await r.json(); } catch(e){ parsed = {}; }
      // preserve HTTP status for smarter handling
      parsed._status = r.status;
      parsed._ok = r.ok;
      return parsed;
    });
  }

  function load(){
    app.innerHTML = '<div class="muted">Loading categories…</div>';
    fetchJson(listUrl).then(res=>{
      renderCategories(res.data||[]);
    }).catch(err=>{ console.error(err); app.innerText = 'Failed to load categories'; });
  }

  // Create form handler
  const createForm = document.getElementById('category-create-form');
  if (createForm) {
    createForm.addEventListener('submit', function(e){
      e.preventDefault();
      clearFormErrors(createForm);
      const fd = new FormData(createForm);
      const body = {};
      fd.forEach((v,k)=> body[k]=v);
      const submitBtn = createForm.querySelector('button[type=submit]');
      if (submitBtn){ submitBtn.classList.add('is-loading'); }
      fetchJson(createUrl, {method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(body)}).then(res=>{
        if (res.data) {
          createForm.reset();
          load();
        } else if (res.errors) {
          // structured field errors
          if (res.errors.name) showFieldError(createForm, 'name', res.errors.name);
          if (res.errors.slug) showFieldError(createForm, 'slug', res.errors.slug);
        } else if (res.error) {
          // map server status to field errors when possible
          if (res._status === 422 || /Name/i.test(res.error)) {
            showFieldError(createForm, 'name', res.error);
          } else if (res._status === 409 || /Slug/i.test(res.error)) {
            showFieldError(createForm, 'slug', res.error);
          } else {
            showInlineError(createForm, res.error || 'Create failed');
          }
        }
      }).catch(err=>{ console.error(err); showInlineError(createForm, 'Create failed'); }).finally(()=>{ if (submitBtn){ submitBtn.classList.remove('is-loading'); } });
    });
  }

  function onDelete(id){
    if(!confirm('Delete this category?')) return;
    const url = deleteUrl.replace('{id}', id);
    const card = document.querySelector('[data-cat-id="' + id + '"]');
    let removedHtml = null;
    if (card) { removedHtml = card.outerHTML; card.classList.add('fade-out'); setTimeout(()=> card.remove(), 220); }
    const deleteBtn = document.querySelector('button[data-id="' + id + '"]');
    if (deleteBtn) { deleteBtn.disabled = true; deleteBtn.classList.add('is-loading'); }
      fetchJson(url, {method:'POST'}).then(res=>{
      if(res._status === 200 || res.status === 'deleted' || res.data) {
        showUndoToast(id, removedHtml, res.data || null);
      } else if(res.error){ alert(res.error); if (removedHtml){ app.querySelector('.card-grid')?.insertAdjacentHTML('afterbegin', removedHtml); } }
    }).catch(err=>{ console.error(err); alert('Delete failed'); if (removedHtml){ app.querySelector('.card-grid')?.insertAdjacentHTML('afterbegin', removedHtml); } }).finally(()=>{ if (deleteBtn){ deleteBtn.disabled = false; deleteBtn.classList.remove('is-loading'); } });
  }

  // Edit modal
  const modal = document.getElementById('category-edit-modal');
  const editForm = document.getElementById('category-edit-form');
  const editCancel = document.getElementById('edit-cancel');
  let _modalKeyHandler = null;
  if (modal) {
    modal.setAttribute('role', 'dialog');
    modal.setAttribute('aria-modal', 'true');
    modal.setAttribute('aria-hidden', 'true');
    // allow clicking the overlay to close
    modal.addEventListener('click', function(e){ if (e.target === modal) closeEditModal(); });
  }
  function openEditModal(category){
    if(!modal) return;
    document.getElementById('edit-id').value = category.id;
    document.getElementById('edit-name').value = category.name || '';
    document.getElementById('edit-slug').value = category.slug || '';
    document.getElementById('edit-color').value = category.color || '';
    modal.style.display = 'grid';
    modal.classList.add('show');
    modal.setAttribute('aria-hidden', 'false');
    setTimeout(()=> { modal.querySelector('.modal-content')?.classList.add('slide-up'); }, 10);
    // attach escape key handler
    _modalKeyHandler = function(e){ if (e.key === 'Escape') closeEditModal(); };
    document.addEventListener('keydown', _modalKeyHandler);
    document.getElementById('edit-name').focus();
  }
  function closeEditModal(){ if(modal){ modal.classList.remove('show'); modal.querySelector('.modal-content')?.classList.remove('slide-up'); setTimeout(()=> modal.style.display = 'none', 240); } }
  if(editCancel){ editCancel.addEventListener('click', closeEditModal); }

  if(editForm){
    editForm.addEventListener('submit', function(e){
      e.preventDefault();
      clearFormErrors(editForm);
      const id = document.getElementById('edit-id').value;
      const name = document.getElementById('edit-name').value;
      const slug = document.getElementById('edit-slug').value;
      const color = document.getElementById('edit-color').value;
      const submitBtn = editForm.querySelector('button[type=submit]');
      if (submitBtn){ submitBtn.classList.add('is-loading'); }
      const url = updateUrl.replace('{id}', id);
      const body = {name:name, slug:slug, color: color};
      fetchJson(url, {method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(body)}).then(res=>{
        if(res.data){ closeEditModal(); load(); }
        else if(res.errors){
          if (res.errors.name) showFieldError(editForm, 'name', res.errors.name);
          if (res.errors.slug) showFieldError(editForm, 'slug', res.errors.slug);
        }
        else if(res.error){
          if (res._status === 422 || /Name/i.test(res.error)) {
            showFieldError(editForm, 'name', res.error);
          } else if (res._status === 409 || /Slug/i.test(res.error)) {
            showFieldError(editForm, 'slug', res.error);
          } else {
            showInlineError(editForm, res.error || 'Update failed');
          }
        }
      }).catch(err=>{ console.error(err); showInlineError(editForm, 'Update failed'); }).finally(()=>{ if (submitBtn){ submitBtn.classList.remove('is-loading'); } });
    });
  }

  function clearFormErrors(form){ const el = form.querySelector('.form-error'); if (el) el.remove(); }

  function showInlineError(form, msg){
    let el = form.querySelector('.form-error');
    if (!el){ el = document.createElement('div'); el.className = 'alert alert-error form-error'; form.prepend(el); }
    el.textContent = msg;
  }

  function showFieldError(form, fieldName, msg){
    clearFieldErrors(form, fieldName);
    const input = form.querySelector('[name="' + fieldName + '"]');
    if (!input){ showInlineError(form, msg); return; }
    const err = document.createElement('div'); err.className = 'field-error muted'; err.style.color = 'var(--accent-amber)'; err.style.marginTop = '6px'; err.textContent = msg;
    input.insertAdjacentElement('afterend', err);
    input.focus();
  }

  function clearFieldErrors(form, fieldName){
    if (fieldName){ const input = form.querySelector('[name="' + fieldName + '"]'); if (!input) return; const next = input.nextElementSibling; if (next && next.classList && next.classList.contains('field-error')) next.remove(); }
    else { const els = form.querySelectorAll('.field-error'); els.forEach(e=>e.remove()); }
  }

  function showUndoToast(id, removedHtml, data){
    let container = document.querySelector('.ck-toast-container');
    if (!container){ container = document.createElement('div'); container.className = 'ck-toast-container'; document.body.appendChild(container); }
    const toast = document.createElement('div'); toast.className = 'ck-toast fade-in';
    const txt = document.createElement('div'); txt.textContent = 'Category deleted';
    const undo = document.createElement('a'); undo.href = '#'; undo.textContent = 'Undo';
    let autoTimer = null;
    function removeToast(delay){ toast.classList.add('fade-out'); setTimeout(()=> toast.remove(), delay || 240); }
    undo.addEventListener('click', function(e){ e.preventDefault();
      const payload = data || {};
      const name = payload.name || parseNameFromHtml(removedHtml) || ('Restored ' + Date.now());
      const slug = payload.slug || ('restored-' + Date.now());
      fetchJson(createUrl, {method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({name: name, slug: slug})}).then(res=>{
        if (res.data){ load(); }
        removeToast(240);
      }).catch(err=>{ console.error(err); alert('Undo failed'); });
    });
    toast.appendChild(txt); toast.appendChild(undo); container.appendChild(toast);
    // pause on hover
    function startAuto(){ autoTimer = setTimeout(()=> removeToast(240), 5000); }
    function stopAuto(){ if (autoTimer) { clearTimeout(autoTimer); autoTimer = null; } }
    toast.addEventListener('mouseenter', stopAuto);
    toast.addEventListener('mouseleave', () => { stopAuto(); startAuto(); });
    startAuto();
  }

  function parseNameFromHtml(html){ try{ const d = document.createElement('div'); d.innerHTML = html; return d.querySelector('h3')?.textContent || null; }catch(e){ return null; } }

  load();
})();
