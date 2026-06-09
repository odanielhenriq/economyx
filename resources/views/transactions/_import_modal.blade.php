{{-- resources/views/transactions/_import_modal.blade.php --}}
{{-- Disparar abertura: window.dispatchEvent(new CustomEvent('open-import')) --}}
<div
  x-data="importModal()"
  x-show="open"
  x-on:keydown.escape.window="reset()"
  class="fixed inset-0 z-50 flex items-center justify-center p-4"
  style="display: none;"
>
  <!-- Overlay -->
  <div class="absolute inset-0 bg-black/50" @click="reset()"></div>

  <!-- Modal -->
  <div class="relative bg-white rounded-xl border border-slate-200 shadow-sm
              w-full max-w-3xl max-h-[90vh] flex flex-col"
       @click.stop>

    <!-- Header -->
    <div class="flex items-center justify-between px-6 py-4
                border-b border-slate-200 flex-shrink-0">
      <div>
        <h2 class="text-base font-semibold text-slate-900">
          Importar extrato
        </h2>
        <p class="text-xs text-slate-400 mt-0.5" x-text="stepLabel"></p>
      </div>
      <button @click="reset()"
              class="text-slate-400 hover:text-slate-600 transition">
        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round"
                stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
        </svg>
      </button>
    </div>

    <!-- Conteúdo scrollável -->
    <div class="flex-1 overflow-y-auto px-6 py-5">

      <!-- STEP 1 — Input -->
      <div x-show="step === 1">

        @unless (config('services.anthropic.key'))
          <div class="mb-4 px-4 py-3 text-sm text-amber-800 bg-amber-50 border border-amber-200 rounded-xl">
            Importação via IA indisponível (ANTHROPIC_API_KEY não configurada). Use lançamento manual ou exporte CSV.
          </div>
        @endunless

        <!-- Tipo de input -->
        <div class="grid grid-cols-2 gap-3 mb-5">
          <button type="button"
            @click="inputType = 'text'"
            :class="inputType === 'text'
              ? 'border-green-500 bg-green-50 text-green-700 ring-2 ring-green-500'
              : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50'"
            class="flex items-center gap-3 p-4 rounded-xl border-2 transition">
            <span class="text-xl">📋</span>
            <div class="text-left">
              <p class="text-sm font-semibold">Colar texto</p>
              <p class="text-xs opacity-70">Copie do app ou PDF</p>
            </div>
          </button>
          <button type="button"
            @click="inputType = 'csv'"
            :class="inputType === 'csv'
              ? 'border-green-500 bg-green-50 text-green-700 ring-2 ring-green-500'
              : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50'"
            class="flex items-center gap-3 p-4 rounded-xl border-2 transition">
            <span class="text-xl">📄</span>
            <div class="text-left">
              <p class="text-sm font-semibold">Arquivo CSV</p>
              <p class="text-xs opacity-70">Baixe no app do banco</p>
            </div>
          </button>
        </div>

        <!-- Textarea para colar texto -->
        <div x-show="inputType === 'text'" class="mb-5">
          <label class="block text-sm font-medium text-slate-700 mb-2">
            Cole o extrato aqui
          </label>
          <textarea
            x-model="rawContent"
            rows="10"
            placeholder="Cole aqui o texto copiado do app do banco, PDF da fatura, e-mail de extrato...

Exemplo:
15/03/2025  Netflix                    R$ 39,90
16/03/2025  Supermercado Extra         R$ 234,50
17/03/2025  Posto Shell                R$ 180,00"
            class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg
                   focus:outline-none focus:ring-2 focus:ring-green-500
                   placeholder:text-slate-300 font-mono resize-none"
          ></textarea>
        </div>

        <!-- Upload de CSV -->
        <div x-show="inputType === 'csv'" class="mb-5">
          <label class="block text-sm font-medium text-slate-700 mb-2">
            Arquivo CSV do banco
          </label>
          <div
            class="border-2 border-dashed border-slate-200 rounded-xl p-8
                   text-center cursor-pointer hover:border-green-400
                   hover:bg-green-50 transition"
            @click="$refs.csvInput.click()"
            @dragover.prevent
            @drop.prevent="handleDrop($event)"
          >
            <svg class="mx-auto h-10 w-10 text-slate-300 mb-3" fill="none"
                 viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round"
                    stroke-width="1.5"
                    d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586
                       a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19
                       a2 2 0 01-2 2z"/>
            </svg>
            <p class="text-sm text-slate-500">
              <span x-text="csvFileName || 'Clique ou arraste o arquivo CSV aqui'"></span>
            </p>
            <p class="text-xs text-slate-400 mt-1">
              Nubank, Inter, Itaú, Bradesco, etc.
            </p>
            <input type="file" x-ref="csvInput" accept=".csv,.ofx,.txt"
                   class="hidden" @change="handleFileSelect($event)" />
          </div>
        </div>

        <!-- Configurações da importação -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-5">

          <!-- Cartão de crédito (opcional) -->
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-2">
              Cartão de crédito
              <span class="text-slate-400 font-normal">(opcional)</span>
            </label>
            <select x-model="creditCardId"
                    class="w-full px-3 py-2 text-sm border border-slate-200
                           rounded-lg focus:outline-none focus:ring-2
                           focus:ring-green-500">
              <option value="">Nenhum (débito ou dinheiro)</option>
              @foreach($creditCards as $card)
                <option value="{{ $card->id }}">{{ $card->alias ?? $card->name }}</option>
              @endforeach
            </select>
          </div>

          <!-- Forma de pagamento -->
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-2">
              Forma de pagamento
            </label>
            <select x-model="paymentMethodId"
                    class="w-full px-3 py-2 text-sm border border-slate-200
                           rounded-lg focus:outline-none focus:ring-2
                           focus:ring-green-500">
              <option value="">Selecione...</option>
              @foreach($paymentMethods as $pm)
                <option value="{{ $pm->id }}">{{ $pm->name }}</option>
              @endforeach
            </select>
          </div>

        </div>

        <!-- Participantes -->
        <div x-show="networkUsers.length > 0" class="mb-5">
          <label class="block text-sm font-medium text-slate-700 mb-2">
            Quem divide esse extrato?
          </label>
          <div class="flex flex-wrap gap-2">
            <template x-for="user in networkUsers" :key="user.id">
              <label class="flex items-center gap-2 px-3 py-2 rounded-lg border
                            border-slate-200 cursor-pointer hover:bg-slate-50 transition"
                     :class="selectedUsers.includes(user.id)
                       ? 'border-green-500 bg-green-50'
                       : ''">
                <input
                  type="checkbox"
                  :value="user.id"
                  x-model="selectedUsers"
                  class="rounded border-slate-300 text-green-600 focus:ring-green-500"
                />
                <span class="text-sm text-slate-700" x-text="user.name"></span>
              </label>
            </template>
          </div>
          <p class="text-xs text-slate-400 mt-1.5">
            Você já está incluído automaticamente.
          </p>
        </div>

        <!-- Aviso sobre IA -->
        <div class="flex items-start gap-2 p-3 bg-slate-50 rounded-lg
                    border border-slate-200">
          <span class="text-base mt-0.5">🤖</span>
          <p class="text-xs text-slate-500">
            A IA vai interpretar o extrato, limpar as descrições e sugerir
            categorias automaticamente. Você poderá revisar tudo antes de importar.
          </p>
        </div>

      </div>

      <!-- STEP 2 — Loading -->
      <div x-show="step === 2" class="py-12 text-center">
        <div class="inline-flex items-center justify-center w-16 h-16
                    bg-green-100 rounded-full mb-4">
          <svg class="animate-spin h-8 w-8 text-green-600" fill="none"
               viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10"
                    stroke="currentColor" stroke-width="4"/>
            <path class="opacity-75" fill="currentColor"
                  d="M4 12a8 8 0 018-8v8H4z"/>
          </svg>
        </div>
        <p class="text-sm font-medium text-slate-900" x-text="loadingMessage"></p>
        <p class="text-xs text-slate-400 mt-1">Isso pode levar alguns segundos</p>
      </div>

      <!-- STEP 3 — Revisão -->
      <div x-show="step === 3">

        <!-- Resumo -->
        <div class="flex items-center gap-4 mb-4 p-4 bg-green-50
                    rounded-xl border border-green-200">
          <div class="text-center">
            <p class="text-2xl font-bold text-green-700"
               x-text="transactions.length"></p>
            <p class="text-xs text-green-600">encontradas</p>
          </div>
          <div class="h-10 w-px bg-green-200"></div>
          <div class="text-center">
            <p class="text-2xl font-bold text-amber-600"
               x-text="transactions.filter(t => !t.category_id).length"></p>
            <p class="text-xs text-amber-600">sem categoria</p>
          </div>
          <div class="h-10 w-px bg-green-200"></div>
          <div class="text-center">
            <p class="text-2xl font-bold text-slate-700"
               x-text="'R$ ' + totalAmount"></p>
            <p class="text-xs text-slate-500">total</p>
          </div>
        </div>

        <!-- Tabela de revisão -->
        <div class="overflow-x-auto rounded-xl border border-slate-200">
          <table class="w-full text-sm min-w-[600px]">
            <thead>
              <tr class="bg-slate-50 border-b border-slate-200">
                <th class="px-4 py-3 text-left text-xs font-semibold
                           text-slate-500 uppercase tracking-wider w-28">
                  Data
                </th>
                <th class="px-4 py-3 text-left text-xs font-semibold
                           text-slate-500 uppercase tracking-wider">
                  Descrição
                </th>
                <th class="px-4 py-3 text-left text-xs font-semibold
                           text-slate-500 uppercase tracking-wider w-40">
                  Categoria
                </th>
                <th class="px-4 py-3 text-right text-xs font-semibold
                           text-slate-500 uppercase tracking-wider w-32">
                  Valor
                </th>
                <th class="px-4 py-3 w-10"></th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <template x-for="(tx, index) in transactions" :key="tx.temp_id">
                <tr :class="!tx.category_id
                  ? 'bg-amber-50 hover:bg-amber-100'
                  : 'hover:bg-slate-50'"
                  class="transition">
                  <td class="px-4 py-3 text-slate-500 text-xs"
                      x-text="formatDate(tx.date)"></td>
                  <td class="px-4 py-3">
                    <div class="flex items-center gap-2">
                      <input
                        type="text"
                        x-model="tx.description"
                        class="flex-1 text-sm text-slate-900 bg-transparent
                               border-0 focus:outline-none focus:ring-1
                               focus:ring-green-500 rounded px-1 -mx-1"
                      />
                      <span
                        x-show="tx.installment_total > 1"
                        class="flex-shrink-0 inline-flex items-center px-2 py-0.5
                               rounded-full text-xs font-medium bg-purple-100 text-purple-700"
                        x-text="tx.installment_number + '/' + tx.installment_total"
                      ></span>
                    </div>
                  </td>
                  <td class="px-4 py-3">
                    <select
                      x-model="tx.category_id"
                      class="w-full text-xs border border-slate-200 rounded-lg
                             px-2 py-1 focus:outline-none focus:ring-1
                             focus:ring-green-500"
                      :class="!tx.category_id
                        ? 'border-amber-300 bg-amber-50'
                        : ''"
                    >
                      <option value="">⚠ Sem categoria</option>
                      @foreach($categories as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                      @endforeach
                    </select>
                  </td>
                  <td class="px-4 py-3 text-right">
                    <span
                      :class="tx.type === 'income'
                        ? 'text-green-600' : 'text-slate-900'"
                      class="text-sm font-semibold tabular-nums"
                      x-text="(tx.type === 'income' ? '+ ' : '') + 'R$ ' +
                        Number(tx.amount).toLocaleString('pt-BR', {
                          minimumFractionDigits: 2
                        })"
                    ></span>
                  </td>
                  <td class="px-4 py-3">
                    <button
                      @click="removeTransaction(index)"
                      title="Remover"
                      class="text-slate-300 hover:text-red-500 transition">
                      <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                           stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                      </svg>
                    </button>
                  </td>
                </tr>
              </template>
            </tbody>
          </table>
        </div>

        <!-- Aviso de sem categoria -->
        <div x-show="transactions.filter(t => !t.category_id).length > 0"
             class="mt-3 flex items-center gap-2 text-xs text-amber-600">
          <span>⚠</span>
          <span>Linhas em amarelo estão sem categoria —
                você pode deixar assim ou selecionar uma antes de importar.</span>
        </div>

      </div>

    </div>

    <!-- Footer com ações -->
    <div class="flex items-center justify-between px-6 py-4
                border-t border-slate-200 flex-shrink-0">

      <!-- Step 1 -->
      <template x-if="step === 1">
        <div class="flex w-full justify-end gap-3">
          <button @click="reset()"
                  class="px-4 py-2 text-sm font-medium text-slate-700
                         bg-white border border-slate-200 rounded-lg
                         hover:bg-slate-50 transition">
            Cancelar
          </button>
          <button
            @click="analyze()"
            :disabled="!canAnalyze"
            :class="canAnalyze
              ? 'bg-green-600 hover:bg-green-700'
              : 'bg-slate-200 text-slate-400 cursor-not-allowed'"
            class="inline-flex items-center gap-2 px-4 py-2 text-sm
                   font-medium text-white rounded-lg transition">
            <span>🤖</span>
            Analisar com IA
          </button>
        </div>
      </template>

      <!-- Step 2 — sem ações -->
      <template x-if="step === 2">
        <div class="w-full"></div>
      </template>

      <!-- Step 3 -->
      <template x-if="step === 3">
        <div class="flex w-full items-center justify-between gap-3">
          <button @click="step = 1"
                  class="text-sm font-medium text-slate-600
                         hover:text-slate-900 transition">
            ← Voltar
          </button>
          <div class="flex gap-3">
            <button @click="reset()"
                    class="px-4 py-2 text-sm font-medium text-slate-700
                           bg-white border border-slate-200 rounded-lg
                           hover:bg-slate-50 transition">
              Cancelar
            </button>
            <button
              @click="save()"
              :disabled="saving || transactions.length === 0"
              class="inline-flex items-center gap-2 px-4 py-2 text-sm
                     font-medium text-white bg-green-600 hover:bg-green-700
                     rounded-lg transition disabled:opacity-50
                     disabled:cursor-not-allowed">
              <svg x-show="saving" class="animate-spin h-4 w-4" fill="none"
                   viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10"
                        stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor"
                      d="M4 12a8 8 0 018-8v8H4z"/>
              </svg>
              <span x-text="saving
                ? 'Importando...'
                : 'Importar ' + transactions.length + ' transações'">
              </span>
            </button>
          </div>
        </div>
      </template>

    </div>
  </div>
</div>

<script>
function importModal() {
  return {
    open: false,
    step: 1,
    inputType: 'text',
    rawContent: '',
    csvFileName: '',
    creditCardId: '',
    paymentMethodId: '',
    transactions: [],
    saving: false,
    networkUsers: [],
    selectedUsers: [{{ auth()->id() }}],

    loadingMessages: [
      'Lendo o extrato...',
      'Identificando transações...',
      'Categorizando com IA...',
      'Quase pronto...',
    ],
    loadingMessage: 'Lendo o extrato...',
    loadingInterval: null,

    init() {
      window.addEventListener('open-import', () => { this.open = true; });
      this.loadNetworkUsers();
    },

    async loadNetworkUsers() {
      try {
        const res  = await fetch('/api/users?user_id={{ auth()->id() }}');
        const data = await res.json();
        const all  = data.data ?? [];
        // Exibe apenas os outros usuários — o auth já está em selectedUsers por padrão
        this.networkUsers = all.filter(u => u.id !== {{ auth()->id() }});
      } catch (e) {
        // silencioso — participantes são opcionais
      }
    },

    get stepLabel() {
      const labels = {
        1: 'Passo 1 de 2 — Carregar extrato',
        2: 'Analisando...',
        3: 'Passo 2 de 2 — Revisar e confirmar',
      };
      return labels[this.step] || '';
    },

    get canAnalyze() {
      return this.rawContent.trim().length > 10 && this.paymentMethodId;
    },

    get totalAmount() {
      return this.transactions
        .filter(t => t.type === 'expense')
        .reduce((sum, t) => sum + Number(t.amount), 0)
        .toLocaleString('pt-BR', { minimumFractionDigits: 2 });
    },

    handleFileSelect(event) {
      const file = event.target.files[0];
      if (!file) return;
      this.csvFileName = file.name;
      const reader = new FileReader();
      reader.onload = (e) => { this.rawContent = e.target.result; };
      reader.readAsText(file, 'UTF-8');
    },

    handleDrop(event) {
      const file = event.dataTransfer.files[0];
      if (!file) return;
      this.csvFileName = file.name;
      const reader = new FileReader();
      reader.onload = (e) => { this.rawContent = e.target.result; };
      reader.readAsText(file, 'UTF-8');
    },

    startLoadingMessages() {
      let i = 0;
      this.loadingMessage = this.loadingMessages[0];
      this.loadingInterval = setInterval(() => {
        i = (i + 1) % this.loadingMessages.length;
        this.loadingMessage = this.loadingMessages[i];
      }, 2000);
    },

    stopLoadingMessages() {
      if (this.loadingInterval) {
        clearInterval(this.loadingInterval);
        this.loadingInterval = null;
      }
    },

    async analyze() {
      if (!this.canAnalyze) return;
      this.step = 2;
      this.startLoadingMessages();

      try {
        const res = await fetch('{{ route("import.analyze") }}', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json',
          },
          body: JSON.stringify({
            content:        this.rawContent,
            credit_card_id: this.creditCardId || null,
          }),
        });

        const data = await res.json();

        if (!res.ok) {
          throw new Error(data.error || 'Erro ao analisar extrato');
        }

        this.transactions = data.transactions;
        this.step = 3;

      } catch (e) {
        window.dispatchEvent(new CustomEvent('toast', {
          detail: { message: e.message || 'Erro ao analisar o extrato.', type: 'error' }
        }));
        this.step = 1;
      } finally {
        this.stopLoadingMessages();
      }
    },

    async save() {
      this.saving = true;
      try {
        const res = await fetch('{{ route("import.store") }}', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json',
          },
          body: JSON.stringify({
            transactions:      this.transactions,
            credit_card_id:    this.creditCardId || null,
            payment_method_id: this.paymentMethodId,
            user_ids:          this.selectedUsers,
          }),
        });

        const data = await res.json();

        if (!res.ok) throw new Error(data.message || 'Erro ao importar');

        window.dispatchEvent(new CustomEvent('toast', {
          detail: { message: data.message, type: 'success' }
        }));

        if (data.failed > 0) {
          window.dispatchEvent(new CustomEvent('toast', {
            detail: {
              message: `${data.failed} ${data.failed === 1 ? 'transação não pôde ser importada' : 'transações não puderam ser importadas'}.`,
              type: 'warning'
            }
          }));
        }

        this.reset();

        if (typeof loadTransactions === 'function') {
          await loadTransactions(1, currentFilters);
        } else {
          window.location.reload();
        }

      } catch (e) {
        window.dispatchEvent(new CustomEvent('toast', {
          detail: { message: e.message || 'Erro ao importar as transações.', type: 'error' }
        }));
      } finally {
        this.saving = false;
      }
    },

    removeTransaction(index) {
      this.transactions.splice(index, 1);
    },

    formatDate(dateStr) {
      if (!dateStr) return '';
      const [y, m, d] = dateStr.split('-');
      return `${d}/${m}/${y}`;
    },

    reset() {
      this.open            = false;
      this.step            = 1;
      this.inputType       = 'text';
      this.rawContent      = '';
      this.csvFileName     = '';
      this.creditCardId    = '';
      this.paymentMethodId = '';
      this.transactions    = [];
      this.saving          = false;
      this.selectedUsers   = [{{ auth()->id() }}];
      this.stopLoadingMessages();
    },
  };
}
</script>
