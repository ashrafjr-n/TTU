{{--
    مودال "إرفاق/تعديل تقرير زيارة" — مودال واحد مشترك لكل الحجوزات في الصفحة.
    يُفتح عبر openVisitReportModal(payload) الموجودة بأسفل هذا الملف، وتُمرَّر
    له بيانات الحجز + التقرير الحالي (إن وُجد) كـ JSON من زر كل حجز.
--}}

<div id="visitReportModal"
     x-data="visitReportModal()"
     x-show="show"
     class="fixed inset-0 z-[100] flex items-center justify-center bg-black/40 backdrop-blur-sm px-4 py-8"
     x-on:keydown.escape.window="show = false"
     style="display: none;">

    {{-- الخلفية --}}
    <div class="absolute inset-0" x-on:click="show = false"></div>

    <div class="relative w-full max-w-2xl max-h-[90vh] overflow-y-auto rounded-[2rem] neu-raised-white p-5 sm:p-8"
         x-show="show"
         x-transition:enter="ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95">

        <button type="button" x-on:click="show = false" title="{{ __('doctor.report_modal.close') }}"
                class="absolute top-6 left-6 w-9 h-9 rounded-full neu-icon-btn bg-ttu-cream text-ttu-gray flex items-center justify-center hover:!bg-ttu-red hover:!text-white">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>

        <h3 class="font-display text-xl font-extrabold mb-6" x-text="isEdit ? labels.editTitle : labels.createTitle"></h3>

        {{-- تنبيه أخطاء عام --}}
        <template x-if="Object.keys(errors).length">
            <div class="rounded-2xl neu-pressed text-red-600 dark:text-red-400 text-sm px-5 py-3.5 mb-6 space-y-1">
                <template x-for="(msgs, field) in errors" :key="field">
                    <p x-text="Array.isArray(msgs) ? msgs[0] : msgs"></p>
                </template>
            </div>
        </template>

        {{-- بيانات المريض (قراءة فقط) --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-6">
            <div class="rounded-xl neu-pressed px-4 py-3">
                <p class="text-[11px] text-ttu-gray mb-1">{{ __('doctor.report_modal.patient_name') }}</p>
                <p class="text-sm font-bold text-ttu-black" x-text="patientName"></p>
            </div>
            <div class="rounded-xl neu-pressed px-4 py-3">
                <p class="text-[11px] text-ttu-gray mb-1">{{ __('doctor.report_modal.patient_identifier') }}</p>
                <p class="text-sm font-bold text-ttu-black" x-text="patientIdentifier"></p>
            </div>
            <div class="rounded-xl neu-pressed px-4 py-3">
                <p class="text-[11px] text-ttu-gray mb-1">{{ __('doctor.report_modal.appointment_date') }}</p>
                <p class="text-sm font-bold text-ttu-black" x-text="dateLabel"></p>
            </div>
            <div class="rounded-xl neu-pressed px-4 py-3">
                <p class="text-[11px] text-ttu-gray mb-1">{{ __('doctor.report_modal.appointment_time') }}</p>
                <p class="text-sm font-bold text-ttu-black" x-text="timeLabel"></p>
            </div>
        </div>

        <form x-ref="form" method="POST" x-bind:action="formAction"
              x-on:submit.prevent="medRows = medRows.filter(r => r.medicationId); $nextTick(() => $refs.form.submit())">
            @csrf
            <input type="hidden" name="booking_id" x-bind:value="bookingId">

            <div class="space-y-4 mb-6">
                <div>
                    <label class="block text-xs font-bold text-ttu-gray mb-1.5">{{ __('doctor.report_modal.condition') }}</label>
                    <textarea name="condition" x-model="condition" rows="2" required
                              class="w-full rounded-xl neu-pressed bg-ttu-cream border-0 px-4 py-3 text-sm focus:ring-2 focus:ring-ttu-red/30 outline-none"></textarea>
                </div>
                <div>
                    <label class="block text-xs font-bold text-ttu-gray mb-1.5">{{ __('doctor.report_modal.examination') }}</label>
                    <textarea name="examination" x-model="examination" rows="2" required
                              class="w-full rounded-xl neu-pressed bg-ttu-cream border-0 px-4 py-3 text-sm focus:ring-2 focus:ring-ttu-red/30 outline-none"></textarea>
                </div>
                <div>
                    <label class="block text-xs font-bold text-ttu-gray mb-1.5">{{ __('doctor.report_modal.diagnosis') }}</label>
                    <textarea name="diagnosis" x-model="diagnosis" rows="2"
                              class="w-full rounded-xl neu-pressed bg-ttu-cream border-0 px-4 py-3 text-sm focus:ring-2 focus:ring-ttu-red/30 outline-none"></textarea>
                </div>
                <div>
                    <label class="block text-xs font-bold text-ttu-gray mb-1.5">{{ __('doctor.report_modal.treatment_plan') }}</label>
                    <textarea name="treatment_plan" x-model="treatmentPlan" rows="2"
                              class="w-full rounded-xl neu-pressed bg-ttu-cream border-0 px-4 py-3 text-sm focus:ring-2 focus:ring-ttu-red/30 outline-none"></textarea>
                </div>
                <div>
                    <label class="block text-xs font-bold text-ttu-gray mb-1.5">{{ __('doctor.report_modal.notes') }}</label>
                    <textarea name="notes" x-model="notes" rows="2"
                              class="w-full rounded-xl neu-pressed bg-ttu-cream border-0 px-4 py-3 text-sm focus:ring-2 focus:ring-ttu-red/30 outline-none"></textarea>
                </div>
            </div>

            {{-- الأدوية --}}
            <div class="mb-6">
                <div class="flex items-center justify-between mb-3">
                    <label class="block text-xs font-bold text-ttu-gray">{{ __('doctor.report_modal.medications') }}</label>
                    <button type="button" x-on:click="addRow()"
                            class="neu-icon-btn bg-ttu-cream text-ttu-black text-xs font-bold px-3 py-1.5 rounded-lg">
                        {{ __('doctor.report_modal.add_medication') }}
                    </button>
                </div>

                <p x-show="medRows.length === 0" class="text-xs text-ttu-gray text-center py-4">{{ __('doctor.report_modal.no_medications_added') }}</p>

                <div class="space-y-3">
                    <template x-for="(row, index) in medRows" :key="row.uid">
                        <div class="flex gap-3 items-start rounded-xl neu-pressed p-3">
                            <div class="flex-1 min-w-0 relative">
                                <input type="text" x-model="row.search"
                                       x-on:focus="row.open = true"
                                       x-on:input="row.open = true; row.medicationId = null"
                                       placeholder="{{ __('doctor.report_modal.search_placeholder') }}"
                                       autocomplete="off"
                                       class="w-full rounded-lg border-0 bg-white dark:bg-ttu-white px-3 py-2 text-sm focus:ring-2 focus:ring-ttu-red/30 outline-none">
                                <input type="hidden" x-bind:name="'medications[' + index + '][medication_id]'" x-bind:value="row.medicationId">

                                <div x-show="row.open" x-on:click.outside="row.open = false"
                                     class="absolute z-10 mt-1 w-full max-h-48 overflow-y-auto rounded-xl neu-raised-white p-2">
                                    <template x-for="med in filteredCatalog(row)" :key="med.id">
                                        <button type="button" x-on:click="selectMed(row, med)"
                                                class="w-full text-right px-3 py-2 rounded-lg hover:bg-ttu-cream text-sm flex items-center justify-between gap-2">
                                            <span x-text="med.name"></span>
                                            <span class="text-[11px] text-ttu-gray shrink-0" x-text="labels.availablePrefix + med.stock + (med.unit ? ' ' + med.unit : '')"></span>
                                        </button>
                                    </template>
                                    <p x-show="filteredCatalog(row).length === 0" class="text-xs text-ttu-gray text-center py-2">{{ __('doctor.report_modal.no_results') }}</p>
                                </div>
                            </div>

                            <input type="number" min="1" x-model.number="row.quantity"
                                   x-bind:name="'medications[' + index + '][quantity]'"
                                   class="w-20 rounded-lg border-0 bg-white dark:bg-ttu-white px-3 py-2 text-sm text-center focus:ring-2 focus:ring-ttu-red/30 outline-none">

                            <button type="button" x-on:click="medRows.splice(index, 1)"
                                    class="w-9 h-9 rounded-full neu-icon-btn bg-white dark:bg-ttu-white text-ttu-red flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </template>
                </div>
            </div>

            <div class="flex gap-3">
                <button type="button" x-on:click="show = false"
                        class="flex-1 neu-icon-btn bg-ttu-cream text-ttu-black text-sm font-bold py-3 rounded-xl">
                    {{ __('doctor.report_modal.cancel') }}
                </button>
                <button type="submit"
                        class="flex-1 neu-icon-btn bg-ttu-red text-white text-sm font-bold py-3 rounded-xl hover:!bg-ttu-red-dark">
                    <span x-text="isEdit ? labels.saveEdit : labels.saveCreate"></span>
                </button>
            </div>
        </form>
    </div>
</div>

@php
    $medicationCatalogJson = $medications->map(fn ($m) => [
        'id' => $m->id,
        'name' => $m->name,
        'unit' => $m->unit,
        'stock' => $m->stock_quantity,
    ])->values()->all();
@endphp

<script>
    function visitReportModal() {
        return {
            show: false,
            actionTemplate: '{{ route('doctor.bookings.report.store', ['booking' => '__ID__']) }}',
            medicationCatalog: @json($medicationCatalogJson),
            labels: {
                editTitle: @json(__('doctor.report_modal.edit_title')),
                createTitle: @json(__('doctor.report_modal.create_title')),
                saveEdit: @json(__('doctor.report_modal.save_edit')),
                saveCreate: @json(__('doctor.report_modal.save_create')),
                availablePrefix: @json(__('doctor.report_modal.available_prefix')),
            },

            bookingId: null,
            isEdit: false,
            patientName: '',
            patientIdentifier: '',
            dateLabel: '',
            timeLabel: '',
            condition: '',
            examination: '',
            diagnosis: '',
            treatmentPlan: '',
            notes: '',
            medRows: [],
            errors: {},
            formAction: '',
            _rowSeq: 0,

            open(payload) {
                this.bookingId = payload.bookingId;
                this.isEdit = !!payload.isEdit;
                this.patientName = payload.patientName || '';
                this.patientIdentifier = payload.patientIdentifier || '';
                this.dateLabel = payload.dateLabel || '';
                this.timeLabel = payload.timeLabel || '';
                this.condition = payload.condition || '';
                this.examination = payload.examination || '';
                this.diagnosis = payload.diagnosis || '';
                this.treatmentPlan = payload.treatmentPlan || '';
                this.notes = payload.notes || '';
                this.errors = payload.errors || {};

                this.medRows = (payload.medications || []).map((m) => {
                    const medId = m.medication_id ? Number(m.medication_id) : null;
                    const cat = this.medicationCatalog.find((c) => c.id === medId);
                    return {
                        uid: this._rowSeq++,
                        medicationId: medId,
                        search: m.name || (cat ? cat.name : ''),
                        quantity: m.quantity ? Number(m.quantity) : 1,
                        open: false,
                    };
                });

                this.formAction = this.actionTemplate.replace('__ID__', payload.bookingId);
                this.show = true;
            },

            addRow() {
                this.medRows.push({ uid: this._rowSeq++, medicationId: null, search: '', quantity: 1, open: false });
            },

            filteredCatalog(row) {
                const chosen = this.medRows.filter((r) => r !== row).map((r) => r.medicationId);
                const q = (row.search || '').toLowerCase();
                return this.medicationCatalog.filter((m) => !chosen.includes(m.id) && m.name.toLowerCase().includes(q));
            },

            selectMed(row, med) {
                row.medicationId = med.id;
                row.search = med.name;
                row.open = false;
            },
        };
    }

    function openVisitReportModal(payload) {
        Alpine.$data(document.getElementById('visitReportModal')).open(payload);
    }
</script>
