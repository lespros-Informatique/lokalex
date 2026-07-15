const API_BASE = (() => {
    const path = window.location.pathname;
    const segments = path.split('/').filter(Boolean);
    return segments.length > 0 ? '/' + segments[0] + '/api' : '/api';
})();

const app = {
    currentUser: null,
    currentShop: null,
    historyFilter: 'today',
    reportPeriod: 'day',
    pendingDelete: null,
    subscriptionMode: 'select',
    devUserPage: 1,
    devShopPage: 1,
    devUserLimit: 20,
    devShopLimit: 20,
    devUserSearch: '',
    devShopSearch: '',
    devUserHasMore: false,
    devShopHasMore: false,
    currentShopCode: null,
    currentUserCode: null,
    shopTxPage: 1,
    shopTxLimit: 15,
    shopTxHasMore: false,
    userTxPage: 1,
    userTxLimit: 15,
    userTxHasMore: false,
    contact: {
        phone: '+225 05 66 01 55 16',
        whatsapp: 'https://wa.me/2250566015516',
        wave: 'Wave',
        orange: 'Orange Money',
    },
    locationFilter: '',
    locationSearch: '',
    locationSearchTimer: null,
    clientSearchTimer: null,
    locationClientSearchTimer: null,
    currentLocationCode: null,

    toast(msg, type = '') {
        const el = document.getElementById('toast');
        const isError = type === 'error';
        const icon = isError
            ? '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>'
            : '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>';
        el.className = 'toast show toast-' + type;
        el.innerHTML = icon + this.escapeHtml(msg);
        clearTimeout(this._toastTimer);
        this._toastTimer = setTimeout(() => el.classList.remove('show'), 2200);
    },

    setButtonLoading(btn, loading) {
        if (!btn) return;
        if (loading) {
            btn.classList.add('btn-loading');
            btn.dataset.originalText = btn.textContent;
            btn.textContent = 'Chargement...';
        } else {
            btn.classList.remove('btn-loading');
            if (btn.dataset.originalText) {
                btn.textContent = btn.dataset.originalText;
            }
        }
    },

    showSkeleton(container, type = 'list') {
        if (!container) return;
        if (type === 'dashboard') {
            container.innerHTML = `
                <div class="metrics-grid">
                    <div class="skeleton-card"><div class="skeleton skeleton-line w-60"></div><div class="skeleton skeleton-line h-24 w-80"></div></div>
                    <div class="skeleton-card"><div class="skeleton skeleton-line w-60"></div><div class="skeleton skeleton-line h-24 w-80"></div></div>
                    <div class="skeleton-card"><div class="skeleton skeleton-line w-60"></div><div class="skeleton skeleton-line h-24 w-80"></div></div>
                </div>`;
        } else if (type === 'list') {
            container.innerHTML = `
                <div class="skeleton-list">
                    <div class="skeleton-list-item"><div class="skeleton skeleton-avatar"></div><div class="skeleton-content"><div class="skeleton skeleton-line w-60"></div><div class="skeleton skeleton-line w-40"></div></div></div>
                    <div class="skeleton-list-item"><div class="skeleton skeleton-avatar"></div><div class="skeleton-content"><div class="skeleton skeleton-line w-60"></div><div class="skeleton skeleton-line w-40"></div></div></div>
                    <div class="skeleton-list-item"><div class="skeleton skeleton-avatar"></div><div class="skeleton-content"><div class="skeleton skeleton-line w-60"></div><div class="skeleton skeleton-line w-40"></div></div></div>
                </div>`;
        }
    },

    init() {
        this.setupEventListeners();
        const saved = localStorage.getItem('lokalex_session');
        if (saved) {
            const s = JSON.parse(saved);
            this.currentUser = s.user;
            this.currentShop = s.shop || null;
            const isDev = this.currentUser && this.currentUser.role_user === 'developpeur';
            document.querySelectorAll('.dev-only').forEach(el => el.style.display = isDev ? '' : 'none');
            document.querySelectorAll('.dev-hidden').forEach(el => el.style.display = isDev ? 'none' : '');
            const menuBtn = document.getElementById('top-menu-btn');
            if (menuBtn) menuBtn.style.display = isDev ? 'flex' : 'none';
            this.navigate('dashboard');
        } else {
            this.navigate('login');
        }
    },

    setupEventListeners() {
        document.addEventListener('click', (e) => {
            const btn = document.getElementById('top-menu-btn');
            const dropdown = document.getElementById('top-menu-dropdown');
            if (btn && dropdown && !btn.contains(e.target) && !dropdown.contains(e.target)) {
                this.closeTopMenu();
            }
        });
        document.querySelectorAll('.nav-item').forEach(item => {
            item.addEventListener('click', () => {
                document.querySelectorAll('.nav-item').forEach(i => i.classList.remove('active'));
                item.classList.add('active');
            });
        });
    },

    navigate(page) {
        document.querySelectorAll('.page').forEach(p => p.classList.remove('active'));
        const target = document.getElementById('page-' + page);
        if (target) target.classList.add('active');

        if (page === 'login') {
            document.body.style.overflow = 'hidden';
        } else {
            document.body.style.overflow = '';
            window.scrollTo({ top: 0, behavior: 'instant' });
        }

        const loggedIn = page !== 'login' && page !== 'subscription';
        const nav = document.getElementById('bottom-nav');
        if (nav) nav.style.display = loggedIn ? 'flex' : 'none';
        const fab = document.getElementById('fab-container');
        if (fab) fab.style.display = (loggedIn && ['dashboard', 'articles', 'clients', 'locations'].includes(page) && this.currentUser?.role_user !== 'developpeur') ? 'flex' : 'none';
        if (fab) fab.classList.remove('open');

        const isDev = this.currentUser && this.currentUser.role_user === 'developpeur';
        const menuBtn = document.getElementById('top-menu-btn');
        if (menuBtn) menuBtn.style.display = loggedIn ? 'flex' : 'none';

        this.closeCreateUserModal();
        this.closeCreateShopModal();
        this.closeUserDetail();
        this.closeConfirm();
        this.closeCreateArticleModal();
        this.closeCreateCategorieModal();
        this.closeCreateClientModal();
        this.closeLocationDetail();

        document.querySelectorAll('.dev-only').forEach(el => el.style.display = isDev ? '' : 'none');
        document.querySelectorAll('.dev-hidden').forEach(el => el.style.display = isDev ? 'none' : '');

        document.querySelectorAll('.nav-item').forEach(i => {
            const pageName = i.dataset.page;
            const active = pageName === page || (isDev && pageName && page.startsWith('dev-') && pageName === page);
            i.classList.toggle('active', active);
        });

        if (page === 'dashboard') this.renderDashboard();
        if (page === 'articles') this.renderArticles();
        if (page === 'categories') this.renderCategories();
        if (page === 'clients') this.renderClients();
        if (page === 'locations') this.renderLocations();
        if (page === 'history') this.renderHistory();
        if (page === 'reports') this.renderReports();
        if (page === 'nouvelle-location') this.renderNouvelleLocation();
        if (page === 'dev-list') { this.devUserPage = 1; this.devUserSearch = ''; const us = document.getElementById('dev-user-search'); if (us) us.value = ''; this.renderDevUsers(); }
        if (page === 'dev-shops') { this.devShopPage = 1; this.devShopSearch = ''; const ss = document.getElementById('dev-shop-search'); if (ss) ss.value = ''; this.renderDevShops(); }
        if (page === 'dev-forfaits') this.renderDevForfaits();
        if (page === 'dev-abonnements') this.renderDevAbonnements();
        if (page === 'subscription') this.renderSubscription();
    },

    async api(url, options = {}) {
        const loader = this._showLoader();
        try {
            const token = this.getAuthToken();
            const headers = {
                'Content-Type': 'application/json',
                ...(token ? { 'Authorization': `Bearer ${token}` } : {}),
            };
            const response = await fetch(`${API_BASE}${url}`, {
                ...options,
                headers: { ...headers, ...options.headers },
                credentials: 'same-origin',
            });
            const text = await response.text();
            let data;
            try {
                data = JSON.parse(text);
            } catch (e) {
                data = { success: false, message: 'Réponse invalide du serveur', data: [] };
            }
            if (!data.success) {
                const err = new Error(data.message || 'Erreur API');
                err.code = data.data?.code ?? null;
                if (err.code === 'SUBSCRIPTION_REQUIRED') {
                    this.subscriptionMode = 'select';
                    this.navigate('subscription');
                } else if (err.code === 'SUBSCRIPTION_EXPIRED') {
                    this.subscriptionMode = 'expired';
                    this.navigate('subscription');
                }
                throw err;
            }
            return data;
        } finally {
            this._hideLoader(loader);
        }
    },

    _showLoader() {
        const overlay = document.createElement('div');
        overlay.className = 'loader-overlay';
        overlay.innerHTML = '<div class="loader-spinner"></div>';
        document.body.appendChild(overlay);
        return overlay;
    },

    _hideLoader(overlay) {
        if (overlay && overlay.parentNode) overlay.parentNode.removeChild(overlay);
    },

    getAuthToken() {
        const match = document.cookie.match(/nafa_token=([^;]+)/);
        return match ? match[1] : null;
    },

    async renderSubscription() {
        const list = document.getElementById('subscription-content');
        if (this.subscriptionMode === 'expired') {
            const c = this.contact;
            list.innerHTML = `
                <div class="expired-box">
                    <div class="expired-icon"><svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"></circle><path d="M12 7v5l3 2"></path></svg></div>
                    <h3 class="expired-title">Votre période d'essai est terminée</h3>
                    <p class="expired-message">Pour continuer à utiliser LOKALEX, veuillez renouveler votre abonnement.</p>
                    <div class="expired-contact">
                        <a class="expired-contact-item" href="tel:${this.escapeHtml(c.phone)}"><span>Contact</span><strong>${this.escapeHtml(c.phone)}</strong></a>
                        <a class="expired-contact-item" href="${this.escapeHtml(c.whatsapp)}" target="_blank" rel="noopener"><span>WhatsApp</span><strong>Écrivez-nous</strong></a>
                        <div class="expired-contact-item"><span>Paiement</span><strong>${this.escapeHtml(c.wave)} / ${this.escapeHtml(c.orange)}</strong></div>
                    </div>
                </div>`;
            return;
        }
        try {
            const data = await this.api('/forfaits');
            const forfaits = data.data.forfaits;
            if (!forfaits.length) { list.innerHTML = '<div class="empty-state">Aucun forfait disponible</div>'; return; }
            list.innerHTML = forfaits.map(f => `
                <div class="list-item">
                    <div class="list-item-info">
                        <div class="list-item-title">${this.escapeHtml(f.libelle_forfait)}</div>
                        <div class="list-item-meta">${this.escapeHtml(f.description_forfait || '')} • ${this.escapeHtml(f.duree_forfait)} j</div>
                    </div>
                    <div class="list-item-actions">
                        <span class="list-item-amount">${this.formatMoney(parseFloat(f.prix_forfait))}</span>
                        <button class="btn btn-primary" onclick="app.subscribe('${this.escapeHtml(f.code_forfait)}')">Choisir</button>
                    </div>
                </div>`).join('');
        } catch (err) {
            this.toast(err.message, 'error');
        }
    },

    async subscribe(forfaitCode) {
        try {
            await this.api('/abonnements', { method: 'POST', body: JSON.stringify({ forfait_code: forfaitCode }) });
            this.toast('Abonnement activé', 'success');
            this.navigate('dashboard');
        } catch (err) {
            this.toast(err.message, 'error');
        }
    },

    async handleLogin(e) {
        e.preventDefault();
        const phone = document.getElementById('phone').value.trim();
        if (!phone) return;
        const btn = e.target.querySelector('button[type="submit"]');
        this.setButtonLoading(btn, true);
        try {
            const data = await this.api('/auth/login', { method: 'POST', body: JSON.stringify({ phone }) });
            this.currentUser = data.data.user;
            this.currentShop = data.data.shop || null;
            this.saveSession();
            this.navigate('dashboard');
            this.toast('Connexion réussie', 'success');
        } catch (err) {
            this.toast(err.message, 'error');
        } finally {
            this.setButtonLoading(btn, false);
        }
    },

    saveSession() {
        localStorage.setItem('lokalex_session', JSON.stringify({ user: this.currentUser, shop: this.currentShop }));
    },

    async logout() {
        this.closeTopMenu();
        const menuBtn = document.getElementById('top-menu-btn');
        this.setButtonLoading(menuBtn, true);
        try {
            await this.api('/auth/logout', { method: 'POST' });
        } catch (e) { /* ignore */ }
        finally {
            this.currentUser = null;
            this.currentShop = null;
            localStorage.removeItem('lokalex_session');
            this.navigate('login');
            this.toast('Déconnexion réussie', 'success');
            this.setButtonLoading(menuBtn, false);
        }
    },

    assetUrl(path) {
        const base = API_BASE.replace(/\/api$/, '');
        if (base && base !== '/') return base.replace(/\/$/, '') + '/' + path.replace(/^\//, '');
        return '/' + path.replace(/^\//, '');
    },

    async downloadApk() {
        this.toast('Aucun fichier APK pour cette version', 'error');
    },

    async renderDashboard() {
        const isDev = this.currentUser && this.currentUser.role_user === 'developpeur';
        if (!isDev && !this.currentShop) return;
        const metricsGrid = document.querySelector('#page-dashboard .metrics-grid');
        const recentList = document.getElementById('recent-list');
        const devSection = document.getElementById('dashboard-dev');
        if (isDev) {
            if (metricsGrid) metricsGrid.style.display = 'none';
            if (devSection) {
                devSection.style.display = '';
                const devMetrics = devSection.querySelector('.metrics-grid');
                if (devMetrics) devMetrics.innerHTML = `
                    <div class="metric-card"><span class="metric-label">Boutiques</span><span class="metric-value">...</span></div>
                    <div class="metric-card"><span class="metric-label">Vendeurs</span><span class="metric-value">...</span></div>
                    <div class="metric-card metric-expenses metric-card-full"><span class="metric-label">Abonnements expirés</span><span class="metric-value">...</span></div>`;
            }
            if (recentList) recentList.style.display = 'none';
        } else {
            if (recentList) this.showSkeleton(recentList, 'list');
            if (metricsGrid) {
                ['dash-en-cours', 'dash-retours', 'dash-montant', 'dash-clients', 'dash-retards'].forEach(id => {
                    const el = document.getElementById(id);
                    if (el) el.textContent = '';
                });
            }
        }
        try {
            const data = await this.api(`/dashboard?client_date=${this.getClientDate()}`);
            const nameEl = document.getElementById('dash-user-name');
            if (nameEl) nameEl.textContent = this.currentUser ? this.currentUser.nom_user : '';
            if (isDev) {
                const s = data.data.stats || {};
                if (devSection) {
                    const devMetrics = devSection.querySelector('.metrics-grid');
                    if (devMetrics) devMetrics.innerHTML = `
                        <div class="metric-card"><span class="metric-label">Boutiques</span><span class="metric-value">${s.boutiques ?? 0}</span></div>
                        <div class="metric-card"><span class="metric-label">Vendeurs</span><span class="metric-value">${s.vendeurs ?? 0}</span></div>
                        <div class="metric-card metric-expenses metric-card-full"><span class="metric-label">Abonnements expirés</span><span class="metric-value">${s.abonnements_expires ?? 0}</span></div>`;
                }
                return;
            }
            if (metricsGrid) {
                const enCours = document.getElementById('dash-en-cours');
                const retours = document.getElementById('dash-retours');
                const montant = document.getElementById('dash-montant');
                const clients = document.getElementById('dash-clients');
                const retards = document.getElementById('dash-retards');
                if (enCours) enCours.textContent = data.data.en_cours ?? 0;
                if (retours) retours.textContent = data.data.retours_prevus ?? 0;
                if (montant) montant.textContent = data.data.montant_jour ?? '0 F';
                if (clients) clients.textContent = data.data.clients ?? 0;
                if (retards) retards.textContent = data.data.retards ?? 0;
            }
            if (recentList) this.renderRecentLocations(data.data.recent);
        } catch (err) {
            this.toast(err.message, 'error');
        }
    },

    renderRecentLocations(locations = []) {
        const list = document.getElementById('recent-list');
        if (!list) return;
        if (!locations.length) { list.innerHTML = '<div class="empty-state">Aucune location récente</div>'; return; }
        list.innerHTML = locations.map(l => `
            <div class="list-item" onclick="app.openLocationDetail('${this.escapeHtml(l.code_location)}')">
                <div class="list-item-info">
                    <div class="list-item-title">${this.escapeHtml(l.nom_client || 'Client')}</div>
                    <div class="list-item-meta">${this.escapeHtml(this.formatFrenchDate(l.created_at_location))}</div>
                </div>
                <span class="list-item-amount">${this.escapeHtml(this.formatMoney(parseFloat(l.montant_location)))}</span>
                ${this.statutBadge(l.statut_location)}
            </div>`).join('');
    },

    statutBadge(statut) {
        const map = { en_cours: ['badge-en_cours', 'En cours'], terminee: ['badge-terminee', 'Terminée'], retard: ['badge-retard', 'En retard'] };
        const [cls, label] = map[statut] || ['badge-inactif', statut];
        return `<span class="badge ${cls}">${label}</span>`;
    },

    async renderArticles() {
        const list = document.getElementById('article-list');
        if (!list) return;
        this.showSkeleton(list, 'list');
        try {
            const data = await this.api('/articles');
            this.articles = data.data.articles || [];
            if (!this.articles.length) { list.innerHTML = '<div class="empty-state">Aucun article. Ajoutez votre matériel.</div>'; return; }
            list.innerHTML = this.articles.map(a => `
                <div class="list-item">
                    <div class="list-item-info">
                        <div class="list-item-title">${this.escapeHtml(a.libelle_article)}</div>
                        <div class="list-item-meta">${this.escapeHtml(a.libelle_categorie || 'Sans catégorie')} • ${this.formatMoney(parseFloat(a.prix_location_article || 0))}</div>
                    </div>
                    <span class="list-item-amount">${this.escapeHtml(String(a.quantite_article))} disp.</span>
                    <span class="badge ${a.statut_article === 'actif' ? 'badge-actif' : 'badge-inactif'}">${a.statut_article}</span>
                    <button class="list-item-arrow" onclick="app.editArticle('${this.escapeHtml(a.code_article)}')">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.12 2.12 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                    </button>
                    ${a.statut_article === 'actif' ? `<button class="list-item-arrow" onclick="app.desactiverArticle('${this.escapeHtml(a.code_article)}')"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V4m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg></button>` : ''}
                </div>`).join('');
        } catch (err) {
            this.toast(err.message, 'error');
        }
    },

    async openCreateArticleModal() {
        await this.loadCategorieOptions('article-categorie');
        document.getElementById('article-code').value = '';
        document.getElementById('article-libelle').value = '';
        document.getElementById('article-categorie').value = '';
        document.getElementById('article-quantite').value = '';
        document.getElementById('article-prix').value = '';
        document.getElementById('article-modal-title').textContent = 'Nouvel article';
        document.getElementById('article-submit').textContent = 'Ajouter';
        document.getElementById('create-article-modal').classList.add('open');
    },

    closeCreateArticleModal() {
        const m = document.getElementById('create-article-modal');
        if (m) m.classList.remove('open');
    },

    editArticle(code) {
        const a = (this.articles || []).find(x => x.code_article === code);
        if (!a) return;
        this.loadCategorieOptions('article-categorie');
        document.getElementById('article-code').value = a.code_article;
        document.getElementById('article-libelle').value = a.libelle_article;
        document.getElementById('article-categorie').value = a.categorie_code || '';
        document.getElementById('article-quantite').value = a.quantite_article;
        document.getElementById('article-prix').value = a.prix_location_article || 0;
        document.getElementById('article-modal-title').textContent = 'Modifier l\'article';
        document.getElementById('article-submit').textContent = 'Enregistrer';
        document.getElementById('create-article-modal').classList.add('open');
    },

    async handleCreateArticle(e) {
        e.preventDefault();
        const code = document.getElementById('article-code').value.trim();
        const payload = {
            libelle: document.getElementById('article-libelle').value.trim(),
            categorie_code: document.getElementById('article-categorie').value.trim(),
            quantite: parseInt(document.getElementById('article-quantite').value, 10) || 0,
            prix_location: parseFloat(document.getElementById('article-prix').value) || 0,
        };
        if (!payload.libelle) { this.toast('Nom requis', 'error'); return; }
        const btn = e.target.querySelector('button[type="submit"]');
        this.setButtonLoading(btn, true);
        try {
            if (code) {
                payload.code_article = code;
                await this.api('/articles/update', { method: 'POST', body: JSON.stringify(payload) });
                this.toast('Article mis à jour', 'success');
            } else {
                await this.api('/articles', { method: 'POST', body: JSON.stringify(payload) });
                this.toast('Article ajouté', 'success');
            }
            this.closeCreateArticleModal();
            this.renderArticles();
        } catch (err) {
            this.toast(err.message, 'error');
        } finally {
            this.setButtonLoading(btn, false);
        }
    },

    async desactiverArticle(code) {
        if (!confirm('Désactiver cet article ?')) return;
        try {
            await this.api('/articles/desactiver', { method: 'POST', body: JSON.stringify({ code_article: code }) });
            this.toast('Article désactivé', 'success');
            this.renderArticles();
        } catch (err) {
            this.toast(err.message, 'error');
        }
    },

    async loadCategorieOptions(selectId) {
        const select = document.getElementById(selectId);
        if (!select) return;
        try {
            const data = await this.api('/categories');
            const categories = data.data.categories || [];
            select.innerHTML = '<option value="">Sans catégorie</option>' + categories.map(c =>
                `<option value="${this.escapeHtml(c.code_categorie)}">${this.escapeHtml(c.libelle_categorie)}</option>`
            ).join('');
        } catch (err) {
            select.innerHTML = '<option value="">Sans catégorie</option>';
        }
    },

    async renderCategories() {
        const list = document.getElementById('categorie-list');
        if (!list) return;
        this.showSkeleton(list, 'list');
        try {
            const data = await this.api('/categories');
            const categories = data.data.categories || [];
            if (!categories.length) { list.innerHTML = '<div class="empty-state">Aucune catégorie.</div>'; return; }
            list.innerHTML = categories.map(c => `
                <div class="list-item">
                    <div class="list-item-info">
                        <div class="list-item-title">${this.escapeHtml(c.libelle_categorie)}</div>
                        <div class="list-item-meta">${this.escapeHtml(c.code_categorie)}</div>
                    </div>
                    <span class="badge ${c.statut_categorie === 'actif' ? 'badge-actif' : 'badge-inactif'}">${c.statut_categorie}</span>
                </div>`).join('');
        } catch (err) {
            this.toast(err.message, 'error');
        }
    },

    openCreateCategorieModal() {
        document.getElementById('categorie-libelle').value = '';
        document.getElementById('create-categorie-modal').classList.add('open');
    },

    closeCreateCategorieModal() {
        const m = document.getElementById('create-categorie-modal');
        if (m) m.classList.remove('open');
    },

    async handleCreateCategorie(e) {
        e.preventDefault();
        const libelle = document.getElementById('categorie-libelle').value.trim();
        if (!libelle) { this.toast('Libellé requis', 'error'); return; }
        const btn = e.target.querySelector('button[type="submit"]');
        this.setButtonLoading(btn, true);
        try {
            await this.api('/categories', { method: 'POST', body: JSON.stringify({ libelle }) });
            this.closeCreateCategorieModal();
            this.toast('Catégorie créée', 'success');
            this.renderCategories();
        } catch (err) {
            this.toast(err.message, 'error');
        } finally {
            this.setButtonLoading(btn, false);
        }
    },

    async renderClients() {
        const list = document.getElementById('client-list');
        if (!list) return;
        this.showSkeleton(list, 'list');
        try {
            const data = await this.api('/clients');
            this.clients = data.data.clients || [];
            this.paintClients(this.clients);
        } catch (err) {
            this.toast(err.message, 'error');
        }
    },

    paintClients(clients) {
        const list = document.getElementById('client-list');
        if (!list) return;
        if (!clients.length) { list.innerHTML = '<div class="empty-state">Aucun client.</div>'; return; }
        list.innerHTML = clients.map(c => `
            <div class="list-item" onclick="app.openClientDetail('${this.escapeHtml(c.code_client)}')">
                <div class="list-item-info">
                    <div class="list-item-title">${this.escapeHtml(c.nom_client)}</div>
                    <div class="list-item-meta">${this.escapeHtml(c.telephone_client || '')} • ${this.escapeHtml(c.adresse_client || '')}</div>
                </div>
                <button class="list-item-arrow" onclick="event.stopPropagation(); app.startLocationWithClient('${this.escapeHtml(c.code_client)}','${this.escapeHtml(c.nom_client)}')">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                </button>
            </div>`).join('');
    },

    onClientSearch(value) {
        clearTimeout(this.clientSearchTimer);
        this.clientSearchTimer = setTimeout(async () => {
            const q = value.trim();
            if (!q) { this.paintClients(this.clients || []); return; }
            try {
                const data = await this.api(`/clients/search?q=${encodeURIComponent(q)}`);
                this.paintClients(data.data.clients || []);
            } catch (err) { /* ignore */ }
        }, 300);
    },

    openCreateClientModal(context) {
        document.getElementById('client-context').value = context || '';
        document.getElementById('client-nom').value = '';
        document.getElementById('client-telephone').value = '';
        document.getElementById('client-adresse').value = '';
        document.getElementById('create-client-modal').classList.add('open');
    },

    closeCreateClientModal() {
        const m = document.getElementById('create-client-modal');
        if (m) m.classList.remove('open');
    },

    async handleCreateClient(e) {
        e.preventDefault();
        const nom = document.getElementById('client-nom').value.trim();
        if (!nom) { this.toast('Nom requis', 'error'); return; }
        const payload = {
            nom,
            telephone: document.getElementById('client-telephone').value.trim(),
            adresse: document.getElementById('client-adresse').value.trim(),
        };
        const btn = e.target.querySelector('button[type="submit"]');
        this.setButtonLoading(btn, true);
        try {
            const data = await this.api('/clients', { method: 'POST', body: JSON.stringify(payload) });
            const client = data.data.client;
            const context = document.getElementById('client-context').value;
            this.closeCreateClientModal();
            if (context === 'location') {
                this.selectLocationClient(client);
                this.toast('Client créé', 'success');
            } else {
                this.toast('Client créé', 'success');
                this.renderClients();
            }
        } catch (err) {
            this.toast(err.message, 'error');
        } finally {
            this.setButtonLoading(btn, false);
        }
    },

    async openClientDetail(code) {
        const modal = document.getElementById('client-detail-modal');
        const sheet = document.getElementById('client-detail-sheet');
        sheet.innerHTML = '<div class="skeleton skeleton-list"><div class="skeleton-list-item"><div class="skeleton skeleton-avatar"></div><div class="skeleton-content"><div class="skeleton skeleton-line w-60"></div><div class="skeleton skeleton-line w-40"></div></div></div></div>';
        modal.classList.add('open');
        try {
            const data = await this.api(`/clients/historique?code=${encodeURIComponent(code)}`);
            const c = data.data.client;
            const locations = data.data.locations || [];
            const locHtml = locations.length ? locations.map(l => `
                <div class="list-item" onclick="app.openLocationDetail('${this.escapeHtml(l.code_location)}')">
                    <div class="list-item-info">
                        <div class="list-item-title">${this.escapeHtml(this.formatFrenchDate(l.created_at_location))}</div>
                        <div class="list-item-meta">${this.formatMoney(parseFloat(l.montant_location))} • reste ${this.formatMoney(parseFloat(l.reste_location))}</div>
                    </div>
                    ${this.statutBadge(l.statut_location)}
                </div>`).join('') : '<div class="empty-state">Aucune location</div>';
            sheet.innerHTML = `
                <div class="modal-header"><h3>Client</h3><button class="modal-close" onclick="app.closeClientDetail()"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg></button></div>
                <div class="modal-body">
                    <div class="detail-section"><h4 class="detail-title">${this.escapeHtml(c.nom_client)}</h4>
                        <div class="detail-grid">
                            <div class="detail-item"><span>Téléphone</span><strong>${this.escapeHtml(c.telephone_client || '-')}</strong></div>
                            <div class="detail-item"><span>Adresse</span><strong>${this.escapeHtml(c.adresse_client || '-')}</strong></div>
                            <div class="detail-item"><span>Locations</span><strong>${data.data.total_locations}</strong></div>
                            <div class="detail-item"><span>Total payé</span><strong>${this.formatMoney(parseFloat(data.data.total_paye))}</strong></div>
                        </div>
                    </div>
                    <div class="detail-section"><h4 class="detail-title">Historique</h4><div class="detail-transactions-scroll">${locHtml}</div></div>
                </div>`;
        } catch (err) {
            sheet.innerHTML = `<div class="empty-state">${this.escapeHtml(err.message)}</div>`;
        }
    },

    closeClientDetail() {
        const m = document.getElementById('client-detail-modal');
        if (m) m.classList.remove('open');
    },

    async renderLocations() {
        const list = document.getElementById('location-list');
        if (!list) return;
        this.showSkeleton(list, 'list');
        const q = this.locationSearch || '';
        let url = '/locations';
        const params = [];
        if (q) params.push('q=' + encodeURIComponent(q));
        if (this.locationFilter === 'retard') params.push('statut=en_cours');
        else if (this.locationFilter) params.push('statut=' + encodeURIComponent(this.locationFilter));
        if (params.length) url += '?' + params.join('&');
        try {
            const data = await this.api(url);
            let locations = data.data.locations || [];
            if (this.locationFilter === 'retard') {
                const today = this.getClientDate();
                locations = locations.filter(l => l.date_retour_prevue_location < today);
            }
            this.paintLocations(locations);
        } catch (err) {
            this.toast(err.message, 'error');
        }
    },

    paintLocations(locations) {
        const list = document.getElementById('location-list');
        if (!list) return;
        if (!locations.length) { list.innerHTML = '<div class="empty-state">Aucune location.</div>'; return; }
        list.innerHTML = locations.map(l => `
            <div class="list-item" onclick="app.openLocationDetail('${this.escapeHtml(l.code_location)}')">
                <div class="list-item-info">
                    <div class="list-item-title">${this.escapeHtml(l.nom_client || 'Client')}</div>
                    <div class="list-item-meta">${this.escapeHtml(this.formatFrenchDate(l.created_at_location))} • ${this.formatMoney(parseFloat(l.montant_location))}</div>
                </div>
                <span class="list-item-amount">${this.formatMoney(parseFloat(l.reste_location))} reste</span>
                ${this.statutBadge(l.statut_location)}
            </div>`).join('');
    },

    setLocationFilter(f) {
        this.locationFilter = f;
        document.querySelectorAll('#page-locations .filter-btn').forEach(b => b.classList.toggle('active', b.dataset.locFilter === f));
        this.renderLocations();
    },

    onLocationSearch(value) {
        clearTimeout(this.locationSearchTimer);
        this.locationSearchTimer = setTimeout(() => {
            this.locationSearch = value.trim();
            this.renderLocations();
        }, 300);
    },

    async openLocationDetail(code) {
        this.currentLocationCode = code;
        const modal = document.getElementById('location-detail-modal');
        const sheet = document.getElementById('location-detail-sheet');
        sheet.innerHTML = '<div class="skeleton skeleton-list"><div class="skeleton-list-item"><div class="skeleton skeleton-avatar"></div><div class="skeleton-content"><div class="skeleton skeleton-line w-60"></div><div class="skeleton skeleton-line w-40"></div></div></div></div>';
        modal.classList.add('open');
        try {
            const data = await this.api(`/locations/show?code=${encodeURIComponent(code)}`);
            this.currentLocation = data.data;
            this.renderLocationDetail();
        } catch (err) {
            sheet.innerHTML = `<div class="empty-state">${this.escapeHtml(err.message)}</div>`;
        }
    },

    renderLocationDetail() {
        const sheet = document.getElementById('location-detail-sheet');
        const d = this.currentLocation;
        if (!d) return;
        const l = d.location;
        const c = d.client || {};
        const lignes = d.lignes || [];
        const paiements = d.paiements || [];
        const lignesHtml = lignes.length ? lignes.map(ll => `
            <div class="list-item">
                <div class="list-item-info"><div class="list-item-title">${this.escapeHtml(ll.libelle_article || ll.article_code)}</div>
                <div class="list-item-meta">${ll.quantite_ligne_location} × ${this.formatMoney(parseFloat(ll.prix_unitaire_ligne_location))}</div></div>
                <span class="list-item-amount">${this.formatMoney(parseFloat(ll.montant_ligne_location))}</span>
            </div>`).join('') : '<div class="empty-state">Aucun article</div>';
        const paiementsHtml = paiements.length ? paiements.map(p => `
            <div class="list-item">
                <div class="list-item-info"><div class="list-item-title">${this.escapeHtml(this.formatFrenchDate(p.created_at_paiement))}</div>
                <div class="list-item-meta">${this.escapeHtml(p.mode_paiement)} ${p.reference_paiement ? '• ' + this.escapeHtml(p.reference_paiement) : ''}</div></div>
                <span class="list-item-amount positive">+${this.formatMoney(parseFloat(p.montant_paiement))}</span>
            </div>`).join('') : '<div class="empty-state">Aucun paiement</div>';
        const terminee = l.statut_location === 'terminee';
        const hasReste = parseFloat(l.reste_location || 0) > 0;
        sheet.innerHTML = `
            <div class="modal-header"><h3>Location</h3><button class="modal-close" onclick="app.closeLocationDetail()"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg></button></div>
            <div class="modal-body">
                <div class="detail-section"><h4 class="detail-title">Client</h4>
                    <div class="detail-grid">
                        <div class="detail-item"><span>Nom</span><strong>${this.escapeHtml(c.nom_client || '-')}</strong></div>
                        <div class="detail-item"><span>Téléphone</span><strong>${this.escapeHtml(c.telephone_client || '-')}</strong></div>
                    </div>
                </div>
                <div class="detail-section"><h4 class="detail-title">Détails</h4>
                    <div class="detail-grid">
                        <div class="detail-item"><span>Sortie</span><strong>${this.escapeHtml(l.date_sortie_location)}</strong></div>
                        <div class="detail-item"><span>Retour prévu</span><strong>${this.escapeHtml(l.date_retour_prevue_location)}</strong></div>
                        <div class="detail-item"><span>Montant</span><strong>${this.formatMoney(parseFloat(l.montant_location))}</strong></div>
                        <div class="detail-item"><span>Avance</span><strong>${this.formatMoney(parseFloat(l.avance_location))}</strong></div>
                        <div class="detail-item"><span>Reste</span><strong>${this.formatMoney(parseFloat(l.reste_location))}</strong></div>
                        <div class="detail-item"><span>Statut</span>${this.statutBadge(l.statut_location)}</div>
                    </div>
                </div>
                <div class="detail-section"><h4 class="detail-title">Articles</h4><div class="detail-transactions-scroll">${lignesHtml}</div></div>
                <div class="detail-section"><h4 class="detail-title">Paiements</h4><div class="detail-transactions-scroll">${paiementsHtml}</div></div>
                <div class="detail-actions">
                    ${terminee ? '' : `<button class="btn btn-primary" onclick="app.openPaiement('${this.escapeHtml(l.code_location)}')">+ Paiement</button>`}
                    ${terminee || hasReste ? '' : `<button class="btn btn-secondary" onclick="app.openRetour('${this.escapeHtml(l.code_location)}')">Retour</button>`}
                </div>
            </div>`;
    },

    closeLocationDetail() {
        const m = document.getElementById('location-detail-modal');
        if (m) m.classList.remove('open');
    },

    openRetour(code) {
        const d = this.currentLocation;
        if (!d) return;
        const reste = parseFloat(d.reste_location || 0);
        if (reste > 0) { this.toast('Impossible de faire un retour tant que le reste à payer est supérieur à 0', 'error'); return; }
        const lignes = d.lignes || [];
        document.getElementById('retour-code').value = code;
        document.getElementById('retour-lignes').innerHTML = lignes.map((ll, i) => `
            <div class="input-group">
                <label>${this.escapeHtml(ll.libelle_article || ll.article_code)} (${ll.quantite_ligne_location} loué(s))</label>
                <input type="number" class="retour-qte" data-article="${this.escapeHtml(ll.article_code)}" data-max="${ll.quantite_ligne_location}" min="0" max="${ll.quantite_ligne_location}" value="0" placeholder="0">
            </div>`).join('');
        document.getElementById('retour-modal').classList.add('open');
    },

    closeRetour() {
        const m = document.getElementById('retour-modal');
        if (m) m.classList.remove('open');
    },

    async handleRetour(e) {
        e.preventDefault();
        const code = document.getElementById('retour-code').value;
        const reste = parseFloat(this.currentLocation?.reste_location || 0);
        if (reste > 0) { this.toast('Impossible de faire un retour tant que le reste à payer est supérieur à 0', 'error'); return; }
        const inputs = document.querySelectorAll('#retour-lignes .retour-qte');
        const lignes = [];
        inputs.forEach(inp => {
            const q = parseInt(inp.value, 10) || 0;
            if (q > 0) lignes.push({ article_code: inp.dataset.article, quantite: q });
        });
        if (!lignes.length) { this.toast('Indiquez les quantités retournées', 'error'); return; }
        const btn = e.target.querySelector('button[type="submit"]');
        this.setButtonLoading(btn, true);
        try {
            await this.api('/locations/retour', { method: 'POST', body: JSON.stringify({ code, lignes }) });
            this.closeRetour();
            this.toast('Retour enregistré', 'success');
            this.openLocationDetail(code);
        } catch (err) {
            this.toast(err.message, 'error');
        } finally {
            this.setButtonLoading(btn, false);
        }
    },

    openPaiement(code) {
        document.getElementById('paiement-code').value = code;
        document.getElementById('paiement-montant').value = '';
        document.getElementById('paiement-reference').value = '';
        document.getElementById('paiement-mode').value = 'especes';
        document.getElementById('paiement-modal').classList.add('open');
    },

    closePaiement() {
        const m = document.getElementById('paiement-modal');
        if (m) m.classList.remove('open');
    },

    async handlePaiement(e) {
        e.preventDefault();
        const code = document.getElementById('paiement-code').value;
        const montant = parseFloat(document.getElementById('paiement-montant').value);
        if (!montant || montant <= 0) { this.toast('Montant invalide', 'error'); return; }
        const reste = parseFloat(this.currentLocation?.reste_location || 0);
        if (montant > reste) { this.toast('Le montant ne peut pas dépasser le reste à payer (' + this.formatMoney(reste) + ')', 'error'); return; }
        const payload = {
            code,
            montant,
            mode_paiement: document.getElementById('paiement-mode').value,
            reference: document.getElementById('paiement-reference').value.trim(),
        };
        const btn = e.target.querySelector('button[type="submit"]');
        this.setButtonLoading(btn, true);
        try {
            await this.api('/locations/paiement', { method: 'POST', body: JSON.stringify(payload) });
            this.closePaiement();
            this.toast('Paiement enregistré', 'success');
            this.openLocationDetail(code);
        } catch (err) {
            this.toast(err.message, 'error');
        } finally {
            this.setButtonLoading(btn, false);
        }
    },

    async renderNouvelleLocation() {
        document.getElementById('location-client-code').value = '';
        document.getElementById('location-client-search').value = '';
        document.getElementById('location-client-results').innerHTML = '';
        document.getElementById('location-client-selected').style.display = 'none';
        document.getElementById('location-date-sortie').value = this.getClientDate();
        document.getElementById('location-date-retour').value = this.getClientDate();
        document.getElementById('location-avance').value = '';
        document.getElementById('location-reste').textContent = '0 F';
        document.getElementById('location-lignes').innerHTML = '';
        try {
            const data = await this.api('/articles');
            this.articleOptions = (data.data.articles || []).filter(a => a.statut_article === 'actif');
        } catch (err) {
            this.articleOptions = [];
        }
        this.addLocationLigne();
    },

    startLocationWithClient(code, name) {
        this.navigate('nouvelle-location');
        setTimeout(() => this.selectLocationClient({ code_client: code, nom_client: name }), 50);
    },

    selectLocationClient(client) {
        document.getElementById('location-client-code').value = client.code_client;
        const chip = document.getElementById('location-client-selected');
        chip.innerHTML = `<span>${this.escapeHtml(client.nom_client)}</span><button type="button" onclick="app.clearLocationClient()">✕</button>`;
        chip.style.display = '';
        document.getElementById('location-client-search').value = '';
        document.getElementById('location-client-results').innerHTML = '';
    },

    clearLocationClient() {
        document.getElementById('location-client-code').value = '';
        document.getElementById('location-client-selected').style.display = 'none';
    },

    onLocationClientSearch(value) {
        clearTimeout(this.locationClientSearchTimer);
        this.locationClientSearchTimer = setTimeout(async () => {
            const q = value.trim();
            const box = document.getElementById('location-client-results');
            if (!q) { box.innerHTML = ''; return; }
            try {
                const data = await this.api(`/clients/search?q=${encodeURIComponent(q)}`);
                const clients = data.data.clients || [];
                box.innerHTML = clients.length ? clients.map(c => `
                    <div class="list-item" onclick="app.selectLocationClient({code_client:'${this.escapeHtml(c.code_client)}', nom_client:'${this.escapeHtml(c.nom_client)}'})">
                        <div class="list-item-info"><div class="list-item-title">${this.escapeHtml(c.nom_client)}</div><div class="list-item-meta">${this.escapeHtml(c.telephone_client || '')}</div></div>
                    </div>`).join('') : '<div class="empty-state">Aucun client</div>';
            } catch (err) { box.innerHTML = ''; }
        }, 300);
    },

    articleSelectHtml() {
        const opts = (this.articleOptions || []).map(a =>
            `<option value="${this.escapeHtml(a.code_article)}" data-prix="${a.prix_location_article}" data-stock="${a.quantite_article}">${this.escapeHtml(a.libelle_article)} (${a.quantite_article} disp.)</option>`
        ).join('');
        return `<select class="ligne-article" onchange="app.onLigneArticleChange(this)"><option value="">Choisir un article</option>${opts}</select>`;
    },

    addLocationLigne() {
        const container = document.getElementById('location-lignes');
        const row = document.createElement('div');
        row.className = 'ligne-row';
        row.innerHTML = `
            ${this.articleSelectHtml()}
            <input type="number" class="ligne-qte" min="1" value="1" placeholder="Qté" oninput="app.calcLocationReste()">
            <input type="number" class="ligne-prix" placeholder="Prix" oninput="app.calcLocationReste()">
            <button type="button" class="ligne-remove" onclick="app.removeLocationLigne(this)">✕</button>`;
        container.appendChild(row);
    },

    onLigneArticleChange(sel) {
        const opt = sel.options[sel.selectedIndex];
        if (opt && opt.dataset.prix) {
            const prix = sel.closest('.ligne-row').querySelector('.ligne-prix');
            if (prix && !prix.value) prix.value = opt.dataset.prix;
        }
        this.calcLocationReste();
    },

    removeLocationLigne(btn) {
        btn.closest('.ligne-row').remove();
        this.calcLocationReste();
    },

    calcLocationReste() {
        let montant = 0;
        document.querySelectorAll('#location-lignes .ligne-row').forEach(row => {
            const q = parseInt(row.querySelector('.ligne-qte').value, 10) || 0;
            const p = parseFloat(row.querySelector('.ligne-prix').value) || 0;
            montant += q * p;
        });
        const avance = parseFloat(document.getElementById('location-avance').value) || 0;
        const reste = Math.max(0, montant - avance);
        document.getElementById('location-reste').textContent = this.formatMoney(reste);
    },

    async handleLocation(e) {
        e.preventDefault();
        const clientCode = document.getElementById('location-client-code').value.trim();
        if (!clientCode) { this.toast('Sélectionnez un client', 'error'); return; }
        const lignes = [];
        let montant = 0;
        document.querySelectorAll('#location-lignes .ligne-row').forEach(row => {
            const articleCode = row.querySelector('.ligne-article').value;
            const q = parseInt(row.querySelector('.ligne-qte').value, 10) || 0;
            const p = parseFloat(row.querySelector('.ligne-prix').value) || 0;
            if (articleCode && q > 0) {
                lignes.push({ article_code: articleCode, quantite: q, prix_unitaire: p });
                montant += q * p;
            }
        });
        if (!lignes.length) { this.toast('Ajoutez au moins un article', 'error'); return; }
        const payload = {
            client_code: clientCode,
            date_sortie: document.getElementById('location-date-sortie').value,
            date_retour_prevue: document.getElementById('location-date-retour').value,
            lignes,
            avance: parseFloat(document.getElementById('location-avance').value) || 0,
        };
        const btn = e.target.querySelector('button[type="submit"]');
        this.setButtonLoading(btn, true);
        try {
            await this.api('/locations', { method: 'POST', body: JSON.stringify(payload) });
            this.toast('Location enregistrée', 'success');
            this.navigate('dashboard');
        } catch (err) {
            this.toast(err.message, 'error');
        } finally {
            this.setButtonLoading(btn, false);
        }
    },

    setHistoryFilter(filter) {
        this.historyFilter = filter;
        document.querySelectorAll('#page-history .filter-btn').forEach(b => b.classList.toggle('active', b.dataset.filter === filter));
        this.renderHistory();
    },

    async renderHistory() {
        const list = document.getElementById('history-list');
        if (!list) return;
        this.showSkeleton(list, 'list');
        try {
            const data = await this.api(`/history?filter=${this.historyFilter}&client_date=${this.getClientDate()}`);
            const items = data.data.items || [];
            list.innerHTML = items.length ? items.map(item => `
                <div class="list-item" onclick="app.openLocationDetail('${this.escapeHtml(item.id)}')">
                    <div class="list-item-info">
                        <div class="list-item-title">${this.escapeHtml(item.title)}</div>
                        <div class="list-item-meta">${this.escapeHtml(item.meta)} • reste ${this.formatMoney(item.reste)}</div>
                    </div>
                    <span class="list-item-amount">${this.formatMoney(item.amount)}</span>
                    ${this.statutBadge(item.statut)}
                </div>`).join('') : '<div class="empty-state">Aucune location</div>';
        } catch (err) {
            this.toast(err.message, 'error');
        }
    },

    async renderReports() {
        const reportSales = document.getElementById('report-sales');
        const reportExpenses = document.getElementById('report-expenses');
        const reportNet = document.getElementById('report-net');
        if (reportSales) reportSales.textContent = '';
        if (reportExpenses) reportExpenses.textContent = '';
        if (reportNet) reportNet.textContent = '';
        try {
            const data = await this.api(`/reports?period=${this.reportPeriod}&client_date=${this.getClientDate()}`);
            if (reportSales) reportSales.textContent = data.data.montant;
            if (reportExpenses) reportExpenses.textContent = data.data.encaisse;
            if (reportNet) reportNet.textContent = data.data.reste;
            this.drawChart(data.data.chart);
        } catch (err) {
            this.toast(err.message, 'error');
        }
    },

    setReportPeriod(period) {
        this.reportPeriod = period;
        document.querySelectorAll('.chart-tab').forEach(t => t.classList.toggle('active', t.dataset.period === period));
        this.renderReports();
    },

    openSearch() {
        const modal = document.getElementById('search-modal');
        modal.classList.add('open');
        const inp = document.getElementById('search-input');
        inp.value = '';
        document.getElementById('search-results').innerHTML = '';
        setTimeout(() => inp.focus(), 100);
    },

    closeSearch() {
        const m = document.getElementById('search-modal');
        if (m) m.classList.remove('open');
    },

    async performSearch(query) {
        const container = document.getElementById('search-results');
        if (!query.trim()) { container.innerHTML = ''; return; }
        this.showSkeleton(container, 'list');
        try {
            const data = await this.api(`/search?q=${encodeURIComponent(query)}`);
            const results = data.data.results || [];
            if (!results.length) { container.innerHTML = '<div class="empty-state">Aucun résultat</div>'; return; }
            container.innerHTML = results.map(item => `
                <div class="list-item" onclick="app.openLocationDetail('${this.escapeHtml(item.id)}'); app.closeSearch();">
                    <div class="list-item-info"><div class="list-item-title">${this.escapeHtml(item.title)}</div><div class="list-item-meta">${this.escapeHtml(item.meta)} • reste ${this.formatMoney(item.reste)}</div></div>
                    <span class="list-item-amount positive">${this.formatMoney(item.amount)}</span>
                    ${this.statutBadge(item.statut)}
                </div>`).join('');
        } catch (err) {
            container.innerHTML = '<div class="empty-state">Erreur recherche</div>';
        }
    },

    async renderDevUsers(append = false) {
        const list = document.getElementById('dev-user-list');
        if (!list) return;
        if (!append) { this.showSkeleton(list, 'list'); this.devUserPage = 1; }
        try {
            const params = new URLSearchParams({ page: this.devUserPage, limit: this.devUserLimit });
            if (this.devUserSearch) params.set('search', this.devUserSearch);
            const data = await this.api(`/dev/users?${params.toString()}`);
            const users = data.data.users;
            const pagination = data.data.pagination || {};
            const html = users.map(u => `
                <div class="list-item">
                    <div class="list-item-info"><div class="list-item-title">${this.escapeHtml(u.nom_user)}</div><div class="list-item-meta">${this.escapeHtml(u.telephone_user)} • <span class="badge badge-role">${this.escapeHtml(u.role_user)}</span></div></div>
                    <span class="list-item-amount">${this.escapeHtml(u.code_user)}</span>
                    <button class="list-item-arrow" onclick="app.openUserDetail('${this.escapeHtml(u.code_user)}')"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg></button>
                </div>`).join('');
            list.innerHTML = append ? (list.innerHTML + html) : (html || '<div class="empty-state">Aucun utilisateur</div>');
            this.devUserHasMore = pagination.has_more || false;
            const btn = document.getElementById('dev-user-load-more');
            if (btn) btn.style.display = this.devUserHasMore ? 'flex' : 'none';
        } catch (err) {
            if (!append) list.innerHTML = '<div class="empty-state">Erreur</div>';
            this.toast(err.message, 'error');
        }
    },

    onDevUserSearch(value) {
        clearTimeout(this._userSearchTimer);
        this._userSearchTimer = setTimeout(() => {
            this.devUserSearch = value;
            this.devUserPage = 1;
            this.renderDevUsers();
        }, 300);
    },

    loadMoreDevUsers() { this.devUserPage++; this.renderDevUsers(true); },

    async openUserDetail(userCode) {
        this.currentUserCode = userCode;
        this.userTxPage = 1;
        this.userTxHasMore = false;
        const modal = document.getElementById('user-detail-modal');
        const sheet = document.getElementById('user-detail-sheet');
        sheet.innerHTML = '<div class="skeleton skeleton-list"><div class="skeleton-list-item"><div class="skeleton skeleton-avatar"></div><div class="skeleton-content"><div class="skeleton skeleton-line w-60"></div><div class="skeleton skeleton-line w-40"></div></div></div></div>';
        modal.classList.add('open');
        try {
            const params = new URLSearchParams({ code: userCode, tx_page: 1, tx_limit: this.userTxLimit });
            const data = await this.api(`/dev/user-detail?${params.toString()}`);
            const user = data.data.user;
            const shop = data.data.shop;
            const transactions = data.data.transactions || [];
            const pagination = data.data.pagination || {};
            this.userTxHasMore = pagination.has_more || false;
            const txHtml = transactions.map(tx => `
                <div class="list-item" onclick="${tx.type === 'location' ? `app.openLocationDetail('${this.escapeHtml(tx.id)}')` : ''}">
                    <div class="list-item-info"><div class="list-item-title">${this.escapeHtml(tx.title || 'Location')}</div><div class="list-item-meta">${this.escapeHtml(this.formatFrenchDate(tx.date))} • ${this.escapeHtml(tx.mode)}</div></div>
                    <span class="list-item-amount">${this.formatMoney(tx.amount)}</span>
                </div>`).join('');
            let html = `
                <div class="modal-header"><h3>Détail utilisateur</h3><button class="modal-close" onclick="app.closeUserDetail()"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg></button></div>
                <div class="modal-body">
                    <div class="detail-section"><h4 class="detail-title">Utilisateur</h4><div class="detail-grid">
                        <div class="detail-item"><span>Nom</span><strong>${this.escapeHtml(user.nom_user)}</strong></div>
                        <div class="detail-item"><span>Téléphone</span><strong>${this.escapeHtml(user.telephone_user)}</strong></div>
                        <div class="detail-item"><span>Rôle</span><strong>${this.escapeHtml(user.role_user)}</strong></div>
                        <div class="detail-item"><span>Statut</span><strong>${this.escapeHtml(user.statut_user)}</strong></div>
                    </div></div>`;
            if (shop) {
                html += `<div class="detail-section"><h4 class="detail-title">Boutique</h4><div class="detail-grid">
                    <div class="detail-item"><span>Libellé</span><strong>${this.escapeHtml(shop.libelle_boutique)}</strong></div>
                    <div class="detail-item"><span>Code</span><strong>${this.escapeHtml(shop.code_boutique)}</strong></div>
                    <div class="detail-item"><span>Ville</span><strong>${this.escapeHtml(shop.ville_boutique || '-')}</strong></div>
                    <div class="detail-item"><span>Statut</span><strong>${this.escapeHtml(shop.statut_boutique)}</strong></div>
                </div></div>`;
            } else {
                html += `<div class="detail-section"><h4 class="detail-title">Boutique</h4><div class="empty-state">Aucune boutique</div></div>`;
            }
            html += `<div class="detail-section"><h4 class="detail-title">Locations</h4><div id="user-detail-transactions" class="detail-transactions-scroll">${txHtml || '<div class="empty-state">Aucune location</div>'}</div>
                <button class="btn-load-more" id="user-detail-load-more" style="display:${this.userTxHasMore ? 'flex' : 'none'}; margin: 12px 20px 8px;" onclick="app.loadMoreUserTransactions()">Charger plus</button></div></div>`;
            sheet.innerHTML = html;
        } catch (err) {
            sheet.innerHTML = `<div class="empty-state">${this.escapeHtml(err.message)}</div>`;
        }
    },

    async loadMoreUserTransactions() {
        if (!this.currentUserCode) return;
        this.userTxPage++;
        const btn = document.getElementById('user-detail-load-more');
        if (btn) { btn.textContent = 'Chargement...'; btn.disabled = true; }
        try {
            const params = new URLSearchParams({ code: this.currentUserCode, tx_page: this.userTxPage, tx_limit: this.userTxLimit });
            const data = await this.api(`/dev/user-detail?${params.toString()}`);
            const transactions = data.data.transactions || [];
            const pagination = data.data.pagination || {};
            this.userTxHasMore = pagination.has_more || false;
            const container = document.getElementById('user-detail-transactions');
            if (container && transactions.length) {
                const txHtml = transactions.map(tx => `
                    <div class="list-item" onclick="${tx.type === 'location' ? `app.openLocationDetail('${this.escapeHtml(tx.id)}')` : ''}">
                        <div class="list-item-info"><div class="list-item-title">${this.escapeHtml(tx.title || 'Location')}</div><div class="list-item-meta">${this.escapeHtml(this.formatFrenchDate(tx.date))} • ${this.escapeHtml(tx.mode)}</div></div>
                        <span class="list-item-amount">${this.formatMoney(tx.amount)}</span>
                    </div>`).join('');
                container.insertAdjacentHTML('beforeend', txHtml);
            }
            if (btn) { btn.textContent = 'Charger plus'; btn.disabled = false; btn.style.display = this.userTxHasMore ? 'flex' : 'none'; }
        } catch (err) {
            this.toast(err.message, 'error');
            if (btn) { btn.textContent = 'Charger plus'; btn.disabled = false; }
        }
    },

    closeUserDetail() { const m = document.getElementById('user-detail-modal'); if (m) m.classList.remove('open'); },

    async renderDevShops(append = false) {
        const list = document.getElementById('dev-shop-list');
        if (!list) return;
        if (!append) { this.showSkeleton(list, 'list'); this.devShopPage = 1; }
        try {
            const params = new URLSearchParams({ page: this.devShopPage, limit: this.devShopLimit });
            if (this.devShopSearch) params.set('search', this.devShopSearch);
            const data = await this.api(`/dev/shops?${params.toString()}`);
            const shops = data.data.shops;
            const pagination = data.data.pagination || {};
            const html = shops.map(s => {
                const statusClass = s.statut_boutique === 'actif' ? 'badge-actif' : 'badge-inactif';
                return `
                <div class="list-item">
                    <div class="list-item-info"><div class="list-item-title">${this.escapeHtml(s.libelle_boutique)}</div><div class="list-item-meta">${this.escapeHtml(s.code_boutique)} • ${this.escapeHtml(s.ville_boutique || '-')}</div></div>
                    <span class="badge ${statusClass}">${this.escapeHtml(s.statut_boutique)}</span>
                    <button class="list-item-arrow" onclick="app.openShopDetail('${s.code_boutique}')"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg></button>
                </div>`;
            }).join('');
            list.innerHTML = append ? (list.innerHTML + html) : (html || '<div class="empty-state">Aucune boutique</div>');
            this.devShopHasMore = pagination.has_more || false;
            const btn = document.getElementById('dev-shop-load-more');
            if (btn) btn.style.display = this.devShopHasMore ? 'flex' : 'none';
        } catch (err) {
            if (!append) list.innerHTML = '<div class="empty-state">Erreur</div>';
            this.toast(err.message, 'error');
        }
    },

    onDevShopSearch(value) {
        clearTimeout(this._shopSearchTimer);
        this._shopSearchTimer = setTimeout(() => {
            this.devShopSearch = value;
            this.devShopPage = 1;
            this.renderDevShops();
        }, 300);
    },

    loadMoreDevShops() { this.devShopPage++; this.renderDevShops(true); },

    async openShopDetail(shopCode) {
        this.currentShopCode = shopCode;
        this.shopTxPage = 1;
        this.shopTxHasMore = false;
        const modal = document.getElementById('user-detail-modal');
        const sheet = document.getElementById('user-detail-sheet');
        sheet.innerHTML = '<div class="skeleton skeleton-list"><div class="skeleton-list-item"><div class="skeleton skeleton-avatar"></div><div class="skeleton-content"><div class="skeleton skeleton-line w-60"></div><div class="skeleton skeleton-line w-40"></div></div></div></div>';
        modal.classList.add('open');
        try {
            const params = new URLSearchParams({ code: shopCode, tx_page: 1, tx_limit: this.shopTxLimit });
            const data = await this.api(`/dev/shop-detail?${params.toString()}`);
            const shop = data.data.shop;
            const transactions = data.data.transactions || [];
            const totals = data.data.totals || {};
            const pagination = data.data.pagination || {};
            this.shopTxHasMore = pagination.has_more || false;
            let forfaitOptions = '';
            try {
                const fData = await this.api('/dev/forfaits');
                forfaitOptions = (fData.data.forfaits || []).map(f => `<option value="${this.escapeHtml(f.code_forfait)}">${this.escapeHtml(f.libelle_forfait)} (${this.formatMoney(parseFloat(f.prix_forfait))})</option>`).join('');
            } catch (e) { /* ignore */ }
            const abonnement = data.data.abonnement;
            const forfait = data.data.forfait;
            const txHtml = transactions.length ? transactions.map(tx => `
                <div class="list-item" onclick="${tx.type === 'location' ? `app.openLocationDetail('${this.escapeHtml(tx.id)}')` : ''}">
                    <div class="list-item-info"><div class="list-item-title">${this.escapeHtml(tx.title || 'Location')}</div><div class="list-item-meta">${this.escapeHtml(this.formatFrenchDate(tx.date))} • ${this.escapeHtml(tx.mode)}</div></div>
                    <span class="list-item-amount">${this.formatMoney(tx.amount)}</span>
                </div>`).join('') : '<div class="empty-state">Aucune location</div>';
            const statutMap = { en_attente: ['En attente', 'badge-en_attente'], actif: ['Actif', 'badge-actif'], expire: ['Expiré', 'badge-expire'], suspendu: ['Suspendu', 'badge-suspendu'] };
            const [statutLabel, statutCls] = (abonnement ? (statutMap[abonnement.statut_abonnement] || [abonnement.statut_abonnement, 'badge-inactif']) : ['Aucun', 'badge-inactif']);
            const abonnementHtml = abonnement ? `
                <div class="detail-item"><span>Forfait</span><strong>${this.escapeHtml(forfait?.libelle_forfait || abonnement.forfait_code || '-')}</strong></div>
                <div class="detail-item"><span>Début</span><strong>${this.escapeHtml(abonnement.date_debut_abonnement || '-')}</strong></div>
                <div class="detail-item"><span>Fin</span><strong>${this.escapeHtml(abonnement.date_fin_abonnement || '-')}</strong></div>
                <div class="detail-item"><span>Montant</span><strong>${this.formatMoney(parseFloat(abonnement.montant_abonnement || 0))}</strong></div>
                <div class="detail-item"><span>Statut</span><span class="badge ${statutCls}">${this.escapeHtml(statutLabel)}</span></div>
            ` : '<div class="empty-state">Aucun abonnement</div>';
            sheet.innerHTML = `
                <div class="modal-header"><h3>Détail boutique</h3><button class="modal-close" onclick="app.closeUserDetail()"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg></button></div>
                <div class="modal-body">
                    <div class="detail-section"><h4 class="detail-title">Abonnement</h4><div class="detail-grid">${abonnementHtml}</div></div>
                    <div class="detail-section"><h4 class="detail-title">Totaux</h4><div class="detail-grid">
                        <div class="detail-item"><span>Montant</span><strong>${totals.sales || '0 F'}</strong></div>
                        <div class="detail-item"><span>Reste</span><strong>${totals.expenses || '0 F'}</strong></div>
                        <div class="detail-item"><span>Encaissé</span><strong>${totals.net || '0 F'}</strong></div>
                    </div></div>
                    <div class="detail-section"><h4 class="detail-title">Réabonnement</h4><div class="reabonnement-row">
                        <select id="shop-reabonnement-forfait" class="abonnement-statut">${forfaitOptions}</select>
                        <button class="btn btn-primary" onclick="app.reabonnement('${this.escapeHtml(shop.code_boutique)}', document.getElementById('shop-reabonnement-forfait').value)">Réabonner</button>
                    </div></div>
                    <div class="detail-section"><h4 class="detail-title">Locations</h4><div id="shop-detail-transactions" class="detail-transactions-scroll">${txHtml}</div>
                        <button class="btn-load-more" id="shop-detail-load-more" style="display:${this.shopTxHasMore ? 'flex' : 'none'}; margin: 12px 20px 8px;" onclick="app.loadMoreShopTransactions()">Charger plus</button></div>
                </div>`;
        } catch (err) {
            sheet.innerHTML = `<div class="empty-state">${this.escapeHtml(err.message)}</div>`;
        }
    },

    async loadMoreShopTransactions() {
        if (!this.currentShopCode) return;
        this.shopTxPage++;
        const btn = document.getElementById('shop-detail-load-more');
        if (btn) { btn.textContent = 'Chargement...'; btn.disabled = true; }
        try {
            const params = new URLSearchParams({ code: this.currentShopCode, tx_page: this.shopTxPage, tx_limit: this.shopTxLimit });
            const data = await this.api(`/dev/shop-detail?${params.toString()}`);
            const transactions = data.data.transactions || [];
            const pagination = data.data.pagination || {};
            this.shopTxHasMore = pagination.has_more || false;
            const container = document.getElementById('shop-detail-transactions');
            if (container && transactions.length) {
                const txHtml = transactions.map(tx => `
                    <div class="list-item" onclick="${tx.type === 'location' ? `app.openLocationDetail('${this.escapeHtml(tx.id)}')` : ''}">
                        <div class="list-item-info"><div class="list-item-title">${this.escapeHtml(tx.title || 'Location')}</div><div class="list-item-meta">${this.escapeHtml(this.formatFrenchDate(tx.date))} • ${this.escapeHtml(tx.mode)}</div></div>
                        <span class="list-item-amount">${this.formatMoney(tx.amount)}</span>
                    </div>`).join('');
                container.insertAdjacentHTML('beforeend', txHtml);
            }
            if (btn) { btn.textContent = 'Charger plus'; btn.disabled = false; btn.style.display = this.shopTxHasMore ? 'flex' : 'none'; }
        } catch (err) {
            this.toast(err.message, 'error');
            if (btn) { btn.textContent = 'Charger plus'; btn.disabled = false; }
        }
    },

    async reabonnement(boutiqueCode, forfaitCode) {
        const btn = document.querySelector(`button[onclick*="'${boutiqueCode}'"]`);
        this.setButtonLoading(btn, true);
        try {
            await this.api('/dev/abonnements', { method: 'POST', body: JSON.stringify({ boutique_code: boutiqueCode, forfait_code: forfaitCode }) });
            this.toast('Abonnement renouvelé', 'success');
            this.openShopDetail(boutiqueCode);
        } catch (err) {
            this.toast(err.message, 'error');
        } finally {
            this.setButtonLoading(btn, false);
        }
    },

    openCreateUserModal() { document.getElementById('create-user-modal').classList.add('open'); },
    closeCreateUserModal() { const m = document.getElementById('create-user-modal'); if (m) m.classList.remove('open'); },

    async handleCreateUser(e) {
        e.preventDefault();
        const phone = document.getElementById('dev-user-phone').value.trim();
        const name = document.getElementById('dev-user-name').value.trim();
        const role = document.getElementById('dev-user-role').value;
        if (!phone || !name) return;
        const btn = e.target.querySelector('button[type="submit"]');
        this.setButtonLoading(btn, true);
        try {
            const data = await this.api('/dev/users', { method: 'POST', body: JSON.stringify({ phone, name, role }) });
            const userCode = data.data.user.code_user;
            document.getElementById('dev-user-phone').value = '';
            document.getElementById('dev-user-name').value = '';
            this.closeCreateUserModal();
            this.openCreateShopModal(userCode);
            this.toast('Utilisateur créé', 'success');
        } catch (err) {
            this.toast(err.message, 'error');
        } finally {
            this.setButtonLoading(btn, false);
        }
    },

    openCreateShopModal(userCode) {
        const codeInput = document.getElementById('dev-shop-user-code');
        if (codeInput && userCode) codeInput.value = userCode;
        this.loadForfaitOptions('dev-shop-forfait', 'DEC001');
        document.getElementById('create-shop-modal').classList.add('open');
    },

    closeCreateShopModal() { const m = document.getElementById('create-shop-modal'); if (m) m.classList.remove('open'); },

    async handleCreateShop(e) {
        e.preventDefault();
        const userCode = document.getElementById('dev-shop-user-code').value.trim();
        const label = document.getElementById('dev-shop-label').value.trim();
        const forfaitCode = document.getElementById('dev-shop-forfait').value.trim();
        if (!userCode || !label) return;
        const btn = e.target.querySelector('button[type="submit"]');
        this.setButtonLoading(btn, true);
        try {
            await this.api('/dev/shops', { method: 'POST', body: JSON.stringify({ user_code: userCode, label, forfait_code: forfaitCode }) });
            document.getElementById('dev-shop-user-code').value = '';
            document.getElementById('dev-shop-label').value = '';
            this.closeCreateShopModal();
            this.toast('Boutique créée', 'success');
            this.renderDevUsers();
        } catch (err) {
            this.toast(err.message, 'error');
        } finally {
            this.setButtonLoading(btn, false);
        }
    },

    async loadForfaitOptions(selectId, selectedCode) {
        const select = document.getElementById(selectId);
        if (!select) return;
        try {
            const data = await this.api('/dev/forfaits');
            const forfaits = data.data.forfaits;
            select.innerHTML = forfaits.map(f => `<option value="${this.escapeHtml(f.code_forfait)}" ${f.code_forfait === selectedCode ? 'selected' : ''}>${this.escapeHtml(f.libelle_forfait)} (${this.formatMoney(parseFloat(f.prix_forfait))})</option>`).join('');
        } catch (err) { this.toast(err.message, 'error'); }
    },

    openCreateForfaitModal() { document.getElementById('create-forfait-modal').classList.add('open'); },
    closeCreateForfaitModal() { const m = document.getElementById('create-forfait-modal'); if (m) m.classList.remove('open'); },

    async handleCreateForfait(e) {
        e.preventDefault();
        const libelle = document.getElementById('dev-forfait-libelle').value.trim();
        const prix = parseFloat(document.getElementById('dev-forfait-prix').value);
        const duree = parseInt(document.getElementById('dev-forfait-duree').value, 10);
        const description = document.getElementById('dev-forfait-description').value.trim();
        if (!libelle || isNaN(prix) || isNaN(duree)) return;
        const btn = e.target.querySelector('button[type="submit"]');
        this.setButtonLoading(btn, true);
        try {
            await this.api('/dev/forfaits', { method: 'POST', body: JSON.stringify({ libelle, prix, duree, description }) });
            document.getElementById('dev-forfait-libelle').value = '';
            document.getElementById('dev-forfait-prix').value = '';
            document.getElementById('dev-forfait-duree').value = '';
            document.getElementById('dev-forfait-description').value = '';
            this.closeCreateForfaitModal();
            this.toast('Forfait créé', 'success');
            this.renderDevForfaits();
        } catch (err) {
            this.toast(err.message, 'error');
        } finally {
            this.setButtonLoading(btn, false);
        }
    },

    async renderDevForfaits() {
        const list = document.getElementById('dev-forfait-list');
        if (!list) return;
        this.showSkeleton(list, 'list');
        try {
            const data = await this.api('/dev/forfaits');
            const forfaits = data.data.forfaits;
            if (!forfaits.length) { list.innerHTML = '<div class="empty-state">Aucun forfait</div>'; return; }
            list.innerHTML = forfaits.map(f => {
                const statusClass = f.statut_forfait === 'actif' ? 'badge-actif' : 'badge-inactif';
                return `<div class="list-item">
                    <div class="list-item-info"><div class="list-item-title">${this.escapeHtml(f.libelle_forfait)}</div><div class="list-item-meta">${this.escapeHtml(f.code_forfait)} • ${this.escapeHtml(f.duree_forfait)} j</div></div>
                    <span class="list-item-amount">${this.formatMoney(parseFloat(f.prix_forfait))}</span>
                    <span class="badge ${statusClass}">${this.escapeHtml(f.statut_forfait)}</span>
                </div>`;
            }).join('');
        } catch (err) { this.toast(err.message, 'error'); }
    },

    async renderDevAbonnements() {
        const list = document.getElementById('dev-abonnement-list');
        if (!list) return;
        this.showSkeleton(list, 'list');
        try {
            const data = await this.api('/dev/abonnements');
            const abonnements = data.data.abonnements;
            if (!abonnements.length) { list.innerHTML = '<div class="empty-state">Aucun abonnement</div>'; return; }
            list.innerHTML = abonnements.map(a => {
                const statusClass = 'badge-' + this.escapeHtml(a.statut_abonnement);
                return `<div class="list-item list-item-column">
                    <div class="list-item-info"><div class="list-item-title">${this.escapeHtml(a.boutique_code)}</div><div class="list-item-meta">${this.escapeHtml(a.forfait_code)} • ${this.formatMoney(parseFloat(a.montant_abonnement))}</div></div>
                    <div class="list-item-actions">
                        <span class="badge ${statusClass}">${this.escapeHtml(a.statut_abonnement)}</span>
                        <select class="abonnement-statut" onchange="app.setAbonnementStatut('${this.escapeHtml(a.code_abonnement)}', this.value)">
                            <option value="en_attente" ${a.statut_abonnement === 'en_attente' ? 'selected' : ''}>En attente</option>
                            <option value="actif" ${a.statut_abonnement === 'actif' ? 'selected' : ''}>Actif</option>
                            <option value="expire" ${a.statut_abonnement === 'expire' ? 'selected' : ''}>Expiré</option>
                            <option value="suspendu" ${a.statut_abonnement === 'suspendu' ? 'selected' : ''}>Suspendu</option>
                        </select>
                    </div>
                </div>`;
            }).join('');
        } catch (err) { this.toast(err.message, 'error'); }
    },

    async setAbonnementStatut(code, statut) {
        const btn = document.querySelector(`button[onclick*="'${code}'"]`);
        this.setButtonLoading(btn, true);
        try {
            await this.api('/dev/abonnement/statut', { method: 'POST', body: JSON.stringify({ code, statut }) });
            this.toast('Statut mis à jour', 'success');
        } catch (err) {
            this.toast(err.message, 'error');
        } finally {
            this.setButtonLoading(btn, false);
            this.renderDevAbonnements();
        }
    },

    toggleFab() {
        const fab = document.getElementById('fab-container');
        if (fab) fab.classList.toggle('open');
    },

    drawChart(chartData) {
        const canvas = document.getElementById('report-chart');
        if (!canvas) return;
        const ctx = canvas.getContext('2d');
        const rect = canvas.parentElement.getBoundingClientRect();
        canvas.width = rect.width - 32;
        canvas.height = 220;
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        const labels = chartData.labels || [];
        const dataV = chartData.sales || [];
        const dataE = chartData.expenses || [];
        const max = Math.max(...dataV, ...dataE, 1);
        const barWidth = (canvas.width - 40) / Math.max(1, labels.length);
        const chartHeight = canvas.height - 60;
        const startX = 20;
        const startY = canvas.height - 40;
        ctx.strokeStyle = '#E5E7EB';
        ctx.lineWidth = 1;
        for (let i = 0; i <= 4; i++) {
            const y = startY - (chartHeight * i / 4);
            ctx.beginPath();
            ctx.moveTo(startX, y);
            ctx.lineTo(startX + barWidth * labels.length, y);
            ctx.stroke();
        }
        labels.forEach((label, i) => {
            const x = startX + i * barWidth + barWidth / 2;
            ctx.fillStyle = '#6B7280';
            ctx.font = '11px Poppins';
            ctx.textAlign = 'center';
            ctx.fillText(label, x, startY + 16);
            const hV = (dataV[i] / max) * chartHeight;
            const hE = (dataE[i] / max) * chartHeight;
            if (hV > 0) { ctx.fillStyle = '#16A34A'; ctx.beginPath(); ctx.roundRect(x - barWidth / 3, startY - hV, barWidth / 3 - 2, hV, 4); ctx.fill(); }
            if (hE > 0) { ctx.fillStyle = '#2563EB'; ctx.beginPath(); ctx.roundRect(x + 2, startY - hE, barWidth / 3 - 2, hE, 4); ctx.fill(); }
        });
    },

    openConfirm() { const m = document.getElementById('confirm-modal'); if (m) m.classList.add('open'); },
    closeConfirm() { const m = document.getElementById('confirm-modal'); if (m) m.classList.remove('open'); this.pendingDelete = null; },

    toggleTopMenu() {
        const dropdown = document.getElementById('top-menu-dropdown');
        if (!dropdown) return;
        const isOpen = dropdown.classList.contains('open');
        if (isOpen) {
            this.closeTopMenu();
        } else {
            dropdown.classList.add('open');
        }
    },

    closeTopMenu() {
        const dropdown = document.getElementById('top-menu-dropdown');
        if (dropdown) dropdown.classList.remove('open');
    },

    handleMenuDownload() {
        this.closeTopMenu();
        this.downloadApk();
    },
    deleteItem(type, id) { this.pendingDelete = { type, id }; this.openConfirm(); },
    async confirmDelete() {
        if (!this.pendingDelete) return;
        const { type, id } = this.pendingDelete;
        const btn = document.querySelector('#confirm-modal .btn-danger');
        this.setButtonLoading(btn, true);
        this.closeConfirm();
        try {
            await this.api('/history/delete', { method: 'POST', body: JSON.stringify({ type, id }) });
            this.renderHistory();
            this.toast('Opération supprimée', 'success');
        } catch (err) {
            this.toast(err.message, 'error');
        } finally {
            this.setButtonLoading(btn, false);
        }
    },

    formatMoney(amount) {
        return new Intl.NumberFormat('fr-FR').format(amount) + ' F';
    },

    formatFrenchDate(dateStr) {
        const date = new Date(dateStr);
        if (isNaN(date.getTime())) return dateStr;
        const days = ['dimanche', 'lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi'];
        const months = ['janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];
        const day = days[date.getDay()];
        const d = date.getDate();
        const month = months[date.getMonth()];
        const year = date.getFullYear();
        const hours = date.getHours();
        const minutes = String(date.getMinutes()).padStart(2, '0');
        return `${day}, ${d} ${month.charAt(0).toUpperCase() + month.slice(1)} ${year} à ${hours}h${minutes}`;
    },

    getClientDate() {
        const d = new Date();
        const year = d.getFullYear();
        const month = String(d.getMonth() + 1).padStart(2, '0');
        const day = String(d.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    },

    escapeHtml(str) {
        return String(str ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    },

    loadMockData() { /* backend handles data */ },
};

document.addEventListener('DOMContentLoaded', () => {
    if (!CanvasRenderingContext2D.prototype.roundRect) {
        CanvasRenderingContext2D.prototype.roundRect = function (x, y, w, h, r) {
            if (w < 2 * r) r = w / 2;
            if (h < 2 * r) r = h / 2;
            this.beginPath();
            this.moveTo(x + r, y);
            this.arcTo(x + w, y, x + w, y + h, r);
            this.arcTo(x + w, y + h, x, y + h, r);
            this.arcTo(x, y + h, x, y, r);
            this.arcTo(x, y, x + w, y, r);
            this.closePath();
            return this;
        };
    }
    app.init();
});
