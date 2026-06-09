/**
 * Lista de configurações com busca local e layout responsivo (tabela + cards mobile).
 */
export function initSearchableList({
    apiUrl,
    tableBody,
    mobileList,
    searchInput,
    emptyDesktopHtml,
    emptyMobileHtml,
    errorHtml,
    errorMobileHtml,
    getSearchText = (item) => item.name ?? '',
    renderTableRow,
    renderMobileCard,
}) {
    let items = [];

    const render = (filtered) => {
        if (!filtered.length) {
            tableBody.innerHTML = emptyDesktopHtml;
            if (mobileList) {
                mobileList.innerHTML = emptyMobileHtml ?? emptyDesktopHtml;
            }
            return;
        }

        tableBody.innerHTML = filtered.map(renderTableRow).join('');
        if (mobileList) {
            mobileList.innerHTML = filtered.map(renderMobileCard).join('');
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

    return fetch(apiUrl, {
        headers: { Accept: 'application/json' },
        credentials: 'same-origin',
    })
        .then((response) => (response.ok ? response.json() : Promise.reject(response)))
        .then((payload) => {
            items = payload.data ?? [];
            render(items);
        })
        .catch(() => {
            tableBody.innerHTML = errorHtml;
            if (mobileList) {
                mobileList.innerHTML = errorMobileHtml ?? '<div class="text-center text-sm text-red-500 py-8">Erro ao carregar.</div>';
            }
        });
}

window.initSearchableList = initSearchableList;
