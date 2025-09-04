<template>
  <section class="tickets">
    <header class="tickets__header">
      <h1 class="tickets__title">Tickets</h1>
      <div class="tickets__actions">
        <button class="button button--primary" @click="openNewTicketModal">New Ticket</button>
      </div>
    </header>

    <div class="tickets__filters">
      <input
        v-model="search"
        class="input input--search"
        type="text"
        placeholder="Search subject or body..."
        @input="applyFilters"
      />
      <select v-model="selectedCategory" class="select" @change="applyFilters">
        <option value="">All categories</option>
        <option v-for="c in categories" :key="c" :value="c">{{ c }}</option>
      </select>
      <select v-model="selectedStatus" class="select" @change="applyFilters">
        <option value="">All status</option>
        <option value="open">open</option>
        <option value="pending">pending</option>
        <option value="closed">closed</option>
      </select>
    </div>

    <div v-if="loading" class="tickets__loading">Loading tickets…</div>
    <div v-else>
      <div v-if="paginatedTickets.length === 0" class="tickets__empty">No tickets found.</div>

      <table class="ticket-table" v-if="viewMode==='table' && paginatedTickets.length">
        <thead>
          <tr>
            <th class="ticket-table__th">Subject</th>
            <th class="ticket-table__th">Category</th>
            <th class="ticket-table__th">Confidence</th>
            <th class="ticket-table__th">Note</th>
            <th class="ticket-table__th">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="t in paginatedTickets" :key="t.id" class="ticket-table__row">
            <td class="ticket-table__td">
              <router-link class="ticket-table__subject" :to="{ name: 'ticket-detail', params: { id: t.id } }" :title="t.body">{{ t.subject }}</router-link>
              <div class="ticket-table__meta">{{ formatDate(t.created_at) }}</div>
            </td>
            <td class="ticket-table__td">
              <span class="badge" :class="'badge--' + (t.category || 'unknown')" :title="t.classification_explanation || 'No explanation'">
                <span class="badge__dot" aria-hidden="true">i</span>
                {{ t.category || '—' }}
              </span>
            </td>
            <td class="ticket-table__td">
              <span v-if="t.classification_confidence !== null && t.classification_confidence !== undefined">
                {{ t.classification_confidence.toFixed(2) }}
              </span>
              <span v-else>—</span>
            </td>
            <td class="ticket-table__td">
              <span v-if="t.note" class="note-badge" title="Internal note present">Note</span>
              <span v-else>—</span>
            </td>
            <td class="ticket-table__td">
              <button
                class="button"
                :disabled="classifying[t.id]"
                @click="classifyTicket(t)"
              >
                <span v-if="classifying[t.id]" class="spinner" aria-hidden="true"></span>
                <span>{{ classifying[t.id] ? 'Classifying…' : 'Classify' }}</span>
              </button>
              <button class="button button--link" @click="editNote(t)">Edit Note</button>
            </td>
          </tr>
        </tbody>
      </table>

      <div class="pagination" v-if="totalPages > 1">
        <button class="button" :disabled="page===1" @click="goToPage(page-1)">Prev</button>
        <span class="pagination__info">Page {{ page }} of {{ totalPages }}</span>
        <button class="button" :disabled="page===totalPages" @click="goToPage(page+1)">Next</button>
      </div>
    </div>

    <!-- New Ticket Modal -->
    <div v-if="showNewModal" class="modal" @click.self="closeNewTicketModal">
      <div class="modal__dialog">
        <h2 class="modal__title">New Ticket</h2>
        <form @submit.prevent="submitNewTicket">
          <label class="form__label">Subject
            <input v-model="newTicket.subject" class="input" required maxlength="255" />
          </label>
          <label class="form__label">Body
            <textarea v-model="newTicket.body" class="textarea" required rows="5"></textarea>
          </label>
          <div class="modal__actions">
            <button type="button" class="button" @click="closeNewTicketModal">Cancel</button>
            <button type="submit" class="button button--primary" :disabled="savingNew">{{ savingNew ? 'Saving…' : 'Create' }}</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Edit Note Modal -->
    <div v-if="showNoteModal" class="modal" @click.self="closeNoteModal">
      <div class="modal__dialog">
        <h2 class="modal__title">Edit Internal Note</h2>
        <form @submit.prevent="submitNote">
          <textarea v-model="noteDraft" class="textarea" rows="6" placeholder="Enter note…"></textarea>
          <div class="modal__actions">
            <button type="button" class="button" @click="closeNoteModal">Cancel</button>
            <button type="submit" class="button button--primary" :disabled="savingNote">{{ savingNote ? 'Saving…' : 'Save Note' }}</button>
          </div>
        </form>
      </div>
    </div>
  </section>
</template>

<script>
export default {
  name: 'TicketsView',
  data() {
    return {
      tickets: [],
      loading: false,
      error: null,

      // filters
      search: '',
      selectedCategory: '',
      selectedStatus: '',

      // pagination
      page: 1,
      perPage: 10,

      // classify state per ticket id
      classifying: {},
      pollers: {},

      // modal states
      showNewModal: false,
      newTicket: { subject: '', body: '' },
      savingNew: false,

      showNoteModal: false,
      editingTicket: null,
      noteDraft: '',
      savingNote: false,

      viewMode: 'table',
    };
  },
  computed: {
    categories() {
      const set = new Set();
      this.tickets.forEach(t => { if (t.category) set.add(t.category); });
      return Array.from(set).sort();
    },
    filteredTickets() {
      const q = this.search.trim().toLowerCase();
      return this.tickets.filter(t => {
        const matchesSearch = !q || (t.subject && t.subject.toLowerCase().includes(q)) || (t.body && t.body.toLowerCase().includes(q));
        const matchesCategory = !this.selectedCategory || t.category === this.selectedCategory;
        const matchesStatus = !this.selectedStatus || t.status === this.selectedStatus;
        return matchesSearch && matchesCategory && matchesStatus;
      });
    },
    totalPages() {
      return Math.max(1, Math.ceil(this.filteredTickets.length / this.perPage));
    },
    paginatedTickets() {
      const start = (this.page - 1) * this.perPage;
      return this.filteredTickets.slice(start, start + this.perPage);
    },
  },
  created() {
    this.fetchTickets();
  },
  methods: {
    async fetchTickets() {
      this.loading = true;
      this.error = null;
      try {
        // Request a high per_page to support client-side pagination
        const res = await window.axios.get('/api/tickets', { params: { per_page: 500 } });
        const data = res.data;
        this.tickets = Array.isArray(data) ? data : (data.data || []);
        // sanitize confidence to number
        this.tickets = this.tickets.map(t => ({
          ...t,
          classification_confidence: typeof t.classification_confidence === 'number' ? t.classification_confidence : (t.classification_confidence ? parseFloat(t.classification_confidence) : null),
        }));
      } catch (e) {
        this.error = 'Failed to load tickets';
        // eslint-disable-next-line no-console
        console.error(e);
      } finally {
        this.loading = false;
      }
    },
    formatDate(iso) {
      if (!iso) return '';
      try {
        const d = new Date(iso);
        return d.toLocaleString();
      } catch (_) {
        return iso;
      }
    },
    applyFilters() {
      this.page = 1;
    },
    goToPage(p) {
      if (p < 1) p = 1;
      if (p > this.totalPages) p = this.totalPages;
      this.page = p;
    },
    openNewTicketModal() {
      this.newTicket = { subject: '', body: '' };
      this.savingNew = false;
      this.showNewModal = true;
    },
    closeNewTicketModal() {
      this.showNewModal = false;
    },
    async submitNewTicket() {
      if (!this.newTicket.subject || !this.newTicket.body) return;
      this.savingNew = true;
      try {
        const res = await window.axios.post('/api/tickets', this.newTicket);
        const t = res.data?.data || res.data;
        if (t) this.tickets.unshift(t);
        this.closeNewTicketModal();
        this.applyFilters();
      } catch (e) {
        // eslint-disable-next-line no-console
        console.error(e);
        alert('Failed to create ticket');
      } finally {
        this.savingNew = false;
      }
    },
    async classifyTicket(t) {
      if (!t || this.classifying[t.id]) return;
      this.$set ? this.$set(this.classifying, t.id, true) : (this.classifying = { ...this.classifying, [t.id]: true });
      try {
        await window.axios.post(`/api/tickets/${t.id}/classify`);
        // Start polling until the classification fields update or timeout
        this.startPolling(t.id);
      } catch (e) {
        // eslint-disable-next-line no-console
        console.error(e);
        alert('Failed to dispatch classification');
        this.$set ? this.$set(this.classifying, t.id, false) : (this.classifying = { ...this.classifying, [t.id]: false });
      }
    },
    startPolling(id) {
      if (this.pollers[id]) clearInterval(this.pollers[id]);
      let attempts = 0;
      const maxAttempts = 20; // ~20s
      this.pollers[id] = setInterval(async () => {
        attempts++;
        try {
          const res = await window.axios.get(`/api/tickets/${id}`);
          const updated = res.data?.data || res.data;
          const idx = this.tickets.findIndex(x => x.id === id);
          if (idx !== -1) this.$set ? this.$set(this.tickets, idx, updated) : (this.tickets = this.tickets.map(x => x.id === id ? updated : x));
          if (updated.classification_confidence !== null && updated.classification_confidence !== undefined) {
            this.stopPolling(id);
            this.$set ? this.$set(this.classifying, id, false) : (this.classifying = { ...this.classifying, [id]: false });
          }
        } catch (e) {
          // ignore transient
        }
        if (attempts >= maxAttempts) {
          this.stopPolling(id);
          this.$set ? this.$set(this.classifying, id, false) : (this.classifying = { ...this.classifying, [id]: false });
        }
      }, 1000);
    },
    stopPolling(id) {
      if (this.pollers[id]) {
        clearInterval(this.pollers[id]);
        delete this.pollers[id];
      }
    },
    editNote(t) {
      this.editingTicket = t;
      this.noteDraft = t.note || '';
      this.showNoteModal = true;
    },
    closeNoteModal() {
      this.showNoteModal = false;
      this.editingTicket = null;
      this.noteDraft = '';
    },
    async submitNote() {
      if (!this.editingTicket) return;
      this.savingNote = true;
      try {
        const res = await window.axios.patch(`/api/tickets/${this.editingTicket.id}`, { note: this.noteDraft });
        const updated = res.data?.data || res.data;
        const idx = this.tickets.findIndex(x => x.id === this.editingTicket.id);
        if (idx !== -1) this.$set ? this.$set(this.tickets, idx, updated) : (this.tickets = this.tickets.map(x => x.id === this.editingTicket.id ? updated : x));
        this.closeNoteModal();
      } catch (e) {
        // eslint-disable-next-line no-console
        console.error(e);
        alert('Failed to save note');
      } finally {
        this.savingNote = false;
      }
    },
  },
  beforeUnmount() {
    Object.keys(this.pollers).forEach(id => this.stopPolling(id));
  },
};
</script>

<style>
/***** Base *****/
.app { padding: 16px; font-family: system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, sans-serif; color: #222; }
.app__title { margin: 0 0 16px; }

/***** Controls *****/
.button { background: #f0f0f0; border: 1px solid #ccc; padding: 6px 10px; border-radius: 4px; cursor: pointer; }
.button:disabled { opacity: 0.6; cursor: default; }
.button--primary { background: #2f6fed; border-color: #2f6fed; color: #fff; }
.button--link { background: transparent; border: none; color: #2f6fed; text-decoration: underline; padding: 0 4px; }
.input, .select, .textarea { border: 1px solid #ccc; border-radius: 4px; padding: 6px 8px; width: 100%; box-sizing: border-box; }
.input--search { max-width: 320px; }
.spinner { width: 12px; height: 12px; border: 2px solid #fff; border-right-color: transparent; border-radius: 50%; display: inline-block; margin-right: 6px; animation: spin 0.8s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }

/***** Tickets *****/
.tickets { max-width: 1200px; margin: 0 auto; padding: 16px; }
.tickets__header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
.tickets__title { margin: 0; }
.tickets__filters { display: flex; gap: 8px; align-items: center; margin-bottom: 12px; }
.tickets__loading, .tickets__empty { padding: 12px; color: #555; }

/***** Table *****/
.ticket-table { width: 100%; border-collapse: collapse; }
.ticket-table__th, .ticket-table__td { text-align: left; border-bottom: 1px solid #eee; padding: 8px; vertical-align: top; }
.ticket-table__subject { font-weight: 600; }
.ticket-table__meta { font-size: 12px; color: #777; }

/***** Badges *****/
.badge { display: inline-flex; align-items: center; gap: 6px; background: #eef2ff; border: 1px solid #c7d2fe; color: #1e3a8a; padding: 2px 6px; border-radius: 999px; }
.badge__dot { display: inline-flex; width: 14px; height: 14px; border-radius: 50%; background: #1e3a8a; color: #fff; font-size: 10px; align-items: center; justify-content: center; }
.badge--billing { background: #fff7ed; border-color: #fed7aa; color: #7c2d12; }
.badge--technical { background: #ecfeff; border-color: #a5f3fc; color: #0e7490; }
.badge--account { background: #f0fdf4; border-color: #bbf7d0; color: #166534; }
.badge--sales { background: #fdf2f8; border-color: #fbcfe8; color: #9d174d; }
.badge--general { background: #eef2ff; border-color: #c7d2fe; color: #1e3a8a; }

.note-badge { display: inline-block; background: #ffe8a3; color: #7a5a00; border: 1px solid #ffd46b; border-radius: 6px; padding: 2px 6px; font-size: 12px; }

/***** Pagination *****/
.pagination { display: flex; gap: 8px; align-items: center; justify-content: flex-end; margin-top: 12px; }
.pagination__info { color: #555; }

/***** Modal *****/
.modal { position: fixed; inset: 0; background: rgba(0,0,0,0.4); display: flex; align-items: center; justify-content: center; }
.modal__dialog { background: #fff; width: 520px; max-width: calc(100% - 24px); border-radius: 8px; padding: 16px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); }
.modal__title { margin-top: 0; }
.modal__actions { display: flex; justify-content: flex-end; gap: 8px; margin-top: 12px; }
.form__label { display: block; margin-bottom: 8px; }
</style>
