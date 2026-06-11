import Alpine from 'alpinejs';

document.addEventListener('alpine:init', () => {
    Alpine.data('sharedExpensesPage', () => ({
        currentUserId: null,
        csrf: '',
        chooseOpen: false,
        confirmOpen: false,
        loading: false,
        chooseParticipants: [],
        pendingAction: null,
        confirmTitle: '',
        confirmMessage: '',
        confirmActionLabel: 'Confirmar',

        init() {
            const config = JSON.parse(this.$el.dataset.config || '{}');
            this.currentUserId = config.currentUserId ?? null;
            this.csrf = config.csrf ?? '';
        },

        formatMoney(value) {
            return Number(value ?? 0).toLocaleString('pt-BR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            });
        },

        openChooseModal(payload) {
            this.pendingAction = payload;
            this.chooseParticipants = payload.participants ?? [];
            this.chooseOpen = true;
        },

        confirmChoose(participant) {
            this.chooseOpen = false;
            this.openSettleModal({
                transactionId: this.pendingAction.transactionId,
                participantId: participant.user_id,
                participantName: participant.name,
                amount: participant.share,
                payerName: this.pendingAction.payerName,
                description: this.pendingAction.description,
                action: this.pendingAction.action,
            });
        },

        openSettleModal(payload) {
            this.pendingAction = payload;
            const amount = this.formatMoney(payload.amount);

            if (payload.action === 'unsettle') {
                this.confirmTitle = 'Desfazer acerto?';
                this.confirmMessage = `Você está desfazendo o acerto da parte de ${payload.participantName} (R$ ${amount}) em ${payload.description}.`;
                this.confirmActionLabel = 'Desfazer acerto';
            } else {
                this.confirmTitle = 'Confirmar acerto?';
                this.confirmMessage = `Você está marcando que ${payload.participantName} pagou R$ ${amount} para ${payload.payerName} referente a ${payload.description}.`;
                this.confirmActionLabel = 'Marcar como acertada';
            }

            this.confirmOpen = true;
        },

        closeModals() {
            this.chooseOpen = false;
            this.confirmOpen = false;
            this.loading = false;
        },

        async submitSettlement() {
            if (!this.pendingAction) {
                return;
            }

            this.loading = true;
            const { transactionId, participantId, action } = this.pendingAction;
            const url = `/shared-expenses/transactions/${transactionId}/participants/${participantId}/settle`;

            try {
                const response = await fetch(url, {
                    method: action === 'unsettle' ? 'DELETE' : 'PATCH',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': this.csrf,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                });

                if (!response.ok) {
                    const data = await response.json().catch(() => ({}));
                    throw new Error(data.message ?? 'Não foi possível concluir o acerto.');
                }

                window.location.reload();
            } catch (error) {
                alert(error.message);
                this.loading = false;
            }
        },
    }));
});
