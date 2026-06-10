/**
 * Lista de configurações com busca local e layout responsivo (tabela + cards mobile).
 */
export function initSearchableList({
    apiUrl,
    tableBody,
    mobileList,
    searchInput,
    loadingEl = null,
    desktopContainer = null,
    mobileContainer = null,
    emptyContainer = null,
    emptyHtml = null,
    errorContainer = null,
    errorHtml = null,
    emptyDesktopHtml,
    emptyMobileHtml,
    errorDesktopHtml = null,
    errorMobileHtml = null,
    getSearchText = (item) => item.name ?? '',
    renderTableRow,
    renderMobileCard,
}) {
    let items = [];

    const hideAll = () => {
        if (loadingEl) loadingEl.classList.add('hidden');
        if (desktopContainer) desktopContainer.classList.add('hidden');
        if (mobileContainer) mobileContainer.classList.add('hidden');
        if (emptyContainer) emptyContainer.classList.add('hidden');
        if (errorContainer) errorContainer.classList.add('hidden');
    };

    const showLoading = () => {
        if (loadingEl) loadingEl.classList.remove('hidden');
        if (desktopContainer) desktopContainer.classList.add('hidden');
        if (mobileContainer) mobileContainer.classList.add('hidden');
        if (emptyContainer) emptyContainer.classList.add('hidden');
        if (errorContainer) errorContainer.classList.add('hidden');
    };

    const render = (filtered) => {
        hideAll();

        if (!filtered.length) {
            if (emptyContainer && emptyHtml && items.length === 0) {
                emptyContainer.innerHTML = emptyHtml;
                emptyContainer.classList.remove('hidden');
                return;
            }

            tableBody.innerHTML = emptyDesktopHtml;
            if (desktopContainer) desktopContainer.classList.remove('hidden');
            if (mobileList) {
                mobileList.innerHTML = emptyMobileHtml ?? emptyDesktopHtml;
                if (mobileContainer) mobileContainer.classList.remove('hidden');
            }
            return;
        }

        tableBody.innerHTML = filtered.map(renderTableRow).join('');
        if (desktopContainer) desktopContainer.classList.remove('hidden');
        if (mobileList) {
            mobileList.innerHTML = filtered.map(renderMobileCard).join('');
            if (mobileContainer) mobileContainer.classList.remove('hidden');
        }
    };

    const filter = (query) => {
        const q = query.trim().toLowerCase();
        if (!q) {
            return items;
        }

        return items.filter((item) => getSearchText(item).toLowerCase().includes(q));
    };

    if (searchInput) {
        searchInput.addEventListener('input', () => render(filter(searchInput.value)));
    }

    showLoading();

    return fetch(apiUrl, {
        headers: { Accept: 'application/json' },
        credentials: 'same-origin',
    })
        .then((response) => (response.ok ? response.json() : Promise.reject(response)))
        .then((payload) => {
            items = payload.data ?? [];
            render(items);
        })
        .catch((error) => {
            console.error('Erro ao carregar lista:', apiUrl, error);
            hideAll();

            if (errorContainer && errorHtml) {
                errorContainer.innerHTML = errorHtml;
                errorContainer.classList.remove('hidden');
                return;
            }

            tableBody.innerHTML = (errorDesktopHtml ?? errorHtml) ?? '<tr><td class="px-4 py-8 text-center text-red-500" colspan="99">Erro ao carregar.</td></tr>';
            if (desktopContainer) desktopContainer.classList.remove('hidden');
            if (mobileList) {
                mobileList.innerHTML = errorMobileHtml ?? '<div class="text-center text-sm text-red-500 py-8">Erro ao carregar.</div>';
                if (mobileContainer) mobileContainer.classList.remove('hidden');
            }
        });
}

window.initSearchableList = initSearchableList;
