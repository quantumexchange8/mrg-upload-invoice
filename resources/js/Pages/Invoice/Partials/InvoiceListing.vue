<script setup>
import {onMounted, ref, watch, watchEffect} from "vue";
import {CalendarIcon, ClockRewindIcon} from "@/Components/Icons/outline.jsx";
import {
    IconPlus,
    IconMinus,
    IconCloudDownload
} from "@tabler/icons-vue";
import {generalFormat, transactionFormat} from "@/Composables/index.js";
import debounce from "lodash/debounce.js";
import { usePage, router} from "@inertiajs/vue3";
import dayjs from "dayjs";
import Button from '@/Components/Button.vue';
import Column from "primevue/column";
import DataTable from "primevue/datatable";
import Tag from "primevue/tag";
import {IconCircleXFilled, IconSearch, IconX, IconAdjustments} from "@tabler/icons-vue";
import InputText from "primevue/inputtext";
import Dropdown from "primevue/dropdown";
import MultiSelect from 'primevue/multiselect';
import DatePicker from 'primevue/datepicker';
import Empty from "@/Components/Empty.vue";
import Loader from "@/Components/Loader.vue";
import DefaultProfilePhoto from "@/Components/DefaultProfilePhoto.vue";
import ColumnGroup from "primevue/columngroup";
import Row from "primevue/row";
import Popover from 'primevue/popover';
import StatusBadge from '@/Components/StatusBadge.vue';
import Badge from '@/Components/Badge.vue';
import RadioButton from 'primevue/radiobutton';
import Dialog from 'primevue/dialog';
import IconField from "primevue/iconfield";

// const props = defineProps({
//   uplines: Array,
// });

const exportStatus = ref(false);
const isLoading = ref(false);
const dt = ref(null);
const invoices = ref();
const selectedInvoices = ref();
const { formatDate, formatAmount } = transactionFormat();
const { formatRgbaColor } = generalFormat();
const totalRecords = ref(0);
const first = ref(0);
const rows = ref(10);
const page = ref(0);
const sortField = ref(null);
const sortOrder = ref(null);  // (1 for ascending, -1 for descending)
const visible = ref(false);

const filters = ref({
    global: null,
    start_date: null,
    end_date: null,
    start_due_date: null,
    end_due_date: null,
});

// Watch for changes on the entire 'filters' object and debounce the API call
watch(filters, debounce(() => {
    const f = filters.value;

    const hasStartRange = f.start_date && f.end_date;
    const hasDueRange = f.start_due_date && f.end_due_date;

    // Skip if either range is partially filled
    if ((f.start_date || f.end_date) && !hasStartRange) return;
    if ((f.start_due_date || f.end_due_date) && !hasDueRange) return;

    let count = 0;

    if (hasStartRange) count += 1;
    if (hasDueRange) count += 1;

    for (const [key, val] of Object.entries(f)) {
        if (['start_date', 'end_date', 'start_due_date', 'end_due_date'].includes(key)) continue;

        if (Array.isArray(val)) {
            if (val.length > 0) count += 1;
        } else if (val !== null && val !== '') {
            count += 1;
        }
    }

    filterCount.value = count;
    page.value = 0;
    loadLazyData();
}, 1000), { deep: true });

const lazyParams = ref({});

const loadLazyData = (event) => {
    isLoading.value = true;

    lazyParams.value = { ...lazyParams.value, first: event?.first || first.value };
    lazyParams.value.filters = filters.value;

    try {
        setTimeout(async () => {
            const params = {
                page: JSON.stringify(event?.page + 1),
                sortField: event?.sortField,
                sortOrder: event?.sortOrder,
                include: [],
                lazyEvent: JSON.stringify(lazyParams.value),
            };

            const url = route('invoice.getInvoicelisting', params);
            const response = await fetch(url);

            const results = await response.json();
            invoices.value = results?.data?.data;
            totalRecords.value = results?.data?.total;
            isLoading.value = false;

        }, 100);
    } catch (error) {
        invoices.value = [];
        totalRecords.value = 0;
        isLoading.value = false;
    }
};

const onPage = (event) => {
    lazyParams.value = event;
    loadLazyData(event);
};

const onSort = (event) => {
    lazyParams.value = event;
    loadLazyData(event);
};

const onFilter = (event) => {
    lazyParams.value.fitlers = filters.value;
    loadLazyData(event);
};

// Optimized exportInvoiceReport function
const exportInvoiceReport = async () => {
    exportStatus.value = true;
    isLoading.value = true;

    lazyParams.value = { ...lazyParams.value, first: event?.first || first.value };
    lazyParams.value.filters = filters.value;

    const selectedIds = selectedInvoices.value.map(invoice => invoice.id);

    const params = {
        page: JSON.stringify(event?.page + 1),
        sortField: event?.sortField,
        sortOrder: event?.sortOrder,
        include: [],
        lazyEvent: JSON.stringify(lazyParams.value),
        exportStatus: true,
        selected_ids: selectedIds.length ? selectedIds : null, // only send if not empty
    };

    const url = route('invoice.getInvoicelisting', params);

    try {
        window.location.href = url;
    } catch (e) {
        console.error('Error occurred during export:', e);
    } finally {
        isLoading.value = false;
        exportStatus.value = false;
    }
};

onMounted(() => {
    // Ensure filters are populated before fetching data
    if (Array.isArray(selectedDate.value)) {
        const [startDate, endDate] = selectedDate.value;
        if (startDate && endDate) {
            filters.value.start_date = startDate;
            filters.value.end_date = endDate;
        }
    }

    lazyParams.value = {
        first: dt.value.first,
        rows: dt.value.rows,
        sortField: null,
        sortOrder: null,
        filters: filters.value
    };

    loadLazyData();
});

const op = ref();
const filterCount = ref(0);
const toggle = (event) => {
    op.value.toggle(event);
}

const clearFilterGlobal = () => {
    filters.value.global = null;
}

watchEffect(() => {
    if (usePage().props.toast !== null) {
        loadLazyData();
    }
});

// Get current date
const today = new Date();

// Define minDate as the start of the current month and maxDate as today
const minDate = ref(new Date(today.getFullYear(), today.getMonth(), 1));
const maxDate = ref(today);

// Reactive variable for selected date range
const selectedDate = ref([minDate.value, maxDate.value]);
const selectedCloseDate = ref(null);

const clearDate = () => {
    selectedDate.value = null;
    filters.value['start_date'] = null;
    filters.value['end_date'] = null;
}

const clearCloseDate = () => {
    selectedCloseDate.value = null;
    filters.value['start_due_date'] = null;
    filters.value['end_due_date'] = null;
}

// Watch for changes in selectedDate
watch(selectedDate, (newDateRange) => {
    if (Array.isArray(newDateRange)) {
        const [startDate, endDate] = newDateRange;
        filters.value['start_date'] = startDate;
        filters.value['end_date'] = endDate;

        // if (startDate !== null && endDate !== null) {
        //     loadLazyData();
        // }
    }
    else {
        // console.warn('Invalid date range format:', newDateRange);
    }
})

// Watch for changes in selectedCloseDate
watch(selectedCloseDate, (newDateRange) => {
    if (Array.isArray(newDateRange)) {
        const [startCloseDate, endCloseDate] = newDateRange;
        filters.value['start_due_date'] = startCloseDate;
        filters.value['end_due_date'] = endCloseDate;

        // Check if both start and end close dates are valid
        // if (startCloseDate !== null && endCloseDate !== null) {
        //     loadLazyData();
        // }
    }
    else {
        // console.warn('Invalid date range format:', newDateRange);
    }
});

const clearFilter = () => {
    filters.value = {
        global: '',
        start_date: null,
        end_date: null,
        start_due_date: null,
        end_due_date: null,
    };

    selectedDate.value = [minDate.value, maxDate.value];
    selectedCloseDate.value = null;
};

const sendEmails = () => {
  const invoiceIds = selectedInvoices.value.map(invoice => invoice.id);

  router.post('/invoice/sendEmails', { invoice_ids: invoiceIds }, {
    preserveScroll: true,
    onSuccess: () => {
    //   console.log(usePage().props); // Now this will include toast!
    },
    onError: (errors) => {
      console.error('Validation failed:', errors);
    }
  });
};

// dialog
const data = ref({});
const openDialog = (rowData) => {
    visible.value = true;
    data.value = rowData;
    fetchInvoiceItems(rowData.id);
};

const invoiceItems = ref([]);
const totalAmount = ref(0);

const fetchInvoiceItems = async (invoiceId) => {
    isLoading.value = true;

    const itemLazyParams = {
        ...lazyParams.value,
        filters: filters.value,
        invoice_id: invoiceId,
    };

    try {
        const params = {
            lazyEvent: JSON.stringify(itemLazyParams),
        };

        const url = route('invoice.getInvoiceItems', params);
        const response = await fetch(url);
        const result = await response.json();

        invoiceItems.value = result?.data || [];
        totalAmount.value = invoiceItems.value.reduce((sum, item) => sum + parseFloat(item.amount), 0);
    } catch (e) {
        console.error('Error fetching invoice items:', e);
        invoiceItems.value = [];
    } finally {
        isLoading.value = false;
    }
};

</script>

<template>
    <div class="flex flex-col items-center px-4 py-6 gap-5 self-stretch rounded-2xl border border-gray-200 bg-white shadow-table md:px-6 md:gap-5">
        <div
            class="w-full"
        >
            <DataTable
                v-model:selection="selectedInvoices"
                :value="invoices"
                :rowsPerPageOptions="[10, 20, 50, 100]"
                lazy
                :paginator="invoices?.length > 0"
                removableSort
                paginatorTemplate="RowsPerPageDropdown FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport"
                :currentPageReportTemplate="$t('public.paginator_caption')"
                :first="first"
                :page="page"
                :rows="10"
                ref="dt"
                dataKey="id"
                selectionMode="multiple"
                @row-click="(event) => openDialog(event.data)"
                :totalRecords="totalRecords"
                :loading="isLoading"
                @page="onPage($event)"
                @sort="onSort($event)"
                @filter="onFilter($event)"
                :globalFilterFields="['email']"
            >
                <template #header>
                    <div class="flex flex-col md:flex-row gap-3 items-center self-stretch pb-3 md:pb-5">
                        <div class="relative w-full md:w-60">
                            <div class="absolute top-2/4 -mt-[9px] left-4 text-gray-400">
                                <IconSearch size="20" stroke-width="1.25" />
                            </div>
                            <InputText v-model="filters['global']" :placeholder="$t('public.keyword_search')" class="font-normal pl-12 w-full md:w-60" />
                            <div
                                v-if="filters['global'] !== null && filters['global'] !== ''"
                                class="absolute top-2/4 -mt-2 right-4 text-gray-300 hover:text-gray-400 select-none cursor-pointer"
                                @click="clearFilterGlobal"
                            >
                                <IconCircleXFilled size="16" />
                            </div>
                        </div>
                        <div class="w-full flex flex-col gap-3 md:flex-row">
                            <div class="w-full md:w-[272px]">
                                <!-- <DatePicker
                                    v-model="selectedDate"
                                    selectionMode="range"
                                    :manualInput="false"
                                    :minDate="minDate"
                                    :maxDate="maxDate"
                                    dateFormat="dd/mm/yy"
                                    showIcon
                                    iconDisplay="input"
                                    placeholder="yyyy/mm/dd - yyyy/mm/dd"
                                    class="w-full md:w-[272px]"
                                />
                                <div
                                    v-if="selectedDate && selectedDate.length > 0"
                                    class="absolute top-2/4 -mt-2.5 right-4 text-gray-400 select-none cursor-pointer bg-white"
                                    @click="clearDate"
                                >
                                    <IconX size="20" />
                                </div> -->
                                <Button
                                    variant="gray-outlined"
                                    @click="toggle"
                                    size="sm"
                                    class="flex gap-3 items-center justify-center py-3 w-full md:w-[130px]"
                                >
                                    <IconAdjustments size="20" color="#0C111D" stroke-width="1.25" />
                                    <div class="text-sm text-gray-950 font-medium">
                                        {{ $t('public.filter') }}
                                    </div>
                                    <Badge class="w-5 h-5 text-xs text-white" variant="numberbadge">
                                        {{ filterCount }}
                                    </Badge>
                                </Button>
                            </div>
                           <div class="w-full flex flex-col md:flex-row justify-end gap-2">
                                <Button
                                    v-if="selectedInvoices?.length > 0"
                                    variant="primary-flat"
                                    :disabled="selectedInvoices?.length === 0"
                                    @click="sendEmails()"
                                >
                                    {{ $t('public.send_email') }}
                                </Button>

                               <Button
                                   variant="primary-outlined"
                                   @click="exportInvoiceReport"
                                   class="w-full md:w-auto"
                               >
                                   {{ $t('public.export') }}
                               </Button>
                           </div>
                        </div>
                    </div>
                </template>
                <template #empty>
                    <Empty
                        :title="$t('public.empty_invoice_title')"
                        :message="$t('public.empty_invoice_message')"
                    />
                </template>
                <template #loading>
                    <div class="flex flex-col gap-2 items-center justify-center">
                        <Loader />
                        <span class="text-sm text-gray-700">{{ $t('public.loading_invoices_data_caption') }}</span>
                    </div>
                </template>
                <template v-if="invoices?.length > 0">
                    <Column selectionMode="multiple" headerStyle="width: 3rem"></Column>
                    <Column
                        field="created_at"
                        sortable
                        :header="`${$t('public.date')}`"
                        class="hidden md:table-cell"
                    >
                        <template #body="slotProps">
                            {{ dayjs(slotProps.data.created_at).format('YYYY/MM/DD') }}
                        </template>
                    </Column>
                    <Column
                        field="doc_date"
                        sortable
                        :header="`${$t('public.doc_date')}`"
                        class="hidden md:table-cell"
                    >
                        <template #body="slotProps">
                            {{ dayjs(slotProps.data.doc_date).format('YYYY/MM/DD') }}
                        </template>
                    </Column>
                    <Column
                        field="doc_no"
                        sortable
                        :header="`${$t('public.doc_no')}`"
                        class="hidden md:table-cell"
                    >
                        <template #body="slotProps">
                            {{ slotProps.data.doc_no }}
                        </template>
                    </Column>
                    <Column
                        field="code"
                        sortable
                        :header="`${$t('public.code')}`"
                        class="hidden md:table-cell"
                    >
                        <template #body="slotProps">
                            {{ slotProps.data.code }}
                        </template>
                    </Column>
                    <Column
                        field="due_date"
                        sortable
                        :header="`${$t('public.due_date')}`"
                        class="hidden md:table-cell"
                    >
                        <template #body="slotProps">
                            {{ dayjs(slotProps.data.due_date).format('YYYY/MM/DD') }}
                        </template>
                    </Column>
                    <Column
                        field="phone"
                        sortable
                        :header="`${$t('public.phone')}`"
                        class="hidden md:table-cell"
                    >
                        <template #body="slotProps">
                            {{ slotProps.data.phone }}
                        </template>
                    </Column>
                    <Column
                        field="email"
                        sortable
                        :header="`${$t('public.email')}`"
                        class="hidden md:table-cell"
                    >
                        <template #body="slotProps">
                            {{ slotProps.data.email }}
                        </template>
                    </Column>

                    <Column class="md:hidden">
                        <template #body="slotProps">
                            <div class="flex items-center justify-between gap-1">
                                <div class="flex items-center gap-3">
                                    <div class="flex flex-col items-start">
                                        <div class="flex flex-wrap items-start gap-x-2">
                                            <div class="text-sm font-semibold w-auto">
                                                {{ slotProps.data.doc_no }}
                                            </div>
                                            <div class="text-sm font-semibold w-auto">
                                                {{ slotProps.data.code }}
                                            </div>
                                        </div>

                                        <div class="text-gray-500 text-xs">
                                            {{ `${$t('public.date')}: ${dayjs(slotProps.data.created_at).format('YYYY/MM/DD')}` }}
                                        </div>
                                        <div class="text-gray-500 text-xs">
                                            {{ `${$t('public.doc_date')}: ${dayjs(slotProps.data.doc_date).format('YYYY/MM/DD')}` }}
                                        </div>
                                    </div>
                                </div>
                                <div class="text-right font-semibold">
                                    {{ dayjs(slotProps.data.due_date).format('YYYY/MM/DD') }}
                                </div>
                            </div>
                        </template>
                    </Column>
                </template>
            </DataTable>
        </div>
    </div>

    <Popover ref="op">
        <div class="flex flex-col gap-8 w-72 py-5 px-4">
            <div class="flex flex-col gap-2 items-center self-stretch">
                <div class="flex self-stretch text-xs text-gray-950 font-semibold">
                    {{ $t('public.filter_date') }}
                </div>
                <div class="flex flex-col relative gap-1 self-stretch">
                    <DatePicker
                        v-model="selectedDate"
                        selectionMode="range"
                        :manualInput="false"
                        :maxDate="maxDate"
                        dateFormat="dd/mm/yy"
                        showIcon
                        iconDisplay="input"
                        placeholder="yyyy/mm/dd - yyyy/mm/dd"
                        class="w-full md:w-[272px]"
                    />
                    <div
                        v-if="selectedDate && selectedDate.length > 0"
                        class="absolute top-2/4 -mt-2.5 right-4 text-gray-400 select-none cursor-pointer bg-white"
                        @click="clearDate"
                    >
                        <IconX size="20" />
                    </div>
                </div>
            </div>

            <div class="flex flex-col gap-2 items-center self-stretch">
                <div class="flex self-stretch text-xs text-gray-950 font-semibold">
                    {{ $t('public.filter_due_date') }}
                </div>
                <div class="flex flex-col relative gap-1 self-stretch">
                    <!-- <DatePicker
                        v-model="selectedCloseDate"
                        selectionMode="range"
                        :manualInput="false"
                        :maxDate="maxDate"
                        dateFormat="dd/mm/yy"
                        showIcon
                        iconDisplay="input"
                        placeholder="yyyy/mm/dd - yyyy/mm/dd"
                        class="w-full md:w-[272px]"
                    />
                    <div
                        v-if="selectedCloseDate && selectedCloseDate.length > 0"
                        class="absolute top-2/4 -mt-2.5 right-4 text-gray-400 select-none cursor-pointer bg-white"
                        @click="clearCloseDate"
                    >
                        <IconX size="20" />
                    </div> -->
                </div>
            </div>

            <div class="flex w-full">
                <Button
                    type="button"
                    variant="primary-outlined"
                    class="flex justify-center w-full"
                    @click="clearFilter()"
                >
                    {{ $t('public.clear_all') }}
                </Button>
            </div>
        </div>
    </Popover>

    <Dialog
        v-model:visible="visible"
        modal
        :header="$t('public.invoice')"
        class="dialog-xs md:dialog-lg lg:w-auto"
    >
        <div class="w-full flex flex-col gap-5">
            <div class="w-full flex flex-col gap-2">
                <div class="w-full flex flex-col md:flex-row gap-5">
                    <div class="w-full flex justify-start gap-1">
                        <span class="whitespace-nowrap py-2">{{ $t('public.bill_to') }}:</span>
                        <div class="flex flex-col px-3 py-2 text-gray-700">
                            <span class="font-bold">{{ data?.company_name }}</span>
                            <span class="uppercase">{{ [data?.address1, data?.address2].filter(Boolean).join(', ') }},</span>
                            <span class="uppercase">{{ [data?.postcode, data?.city, data?.state, data?.country].filter(Boolean).join(', ') }}.</span>
                            <span class="uppercase">{{ $t('public.tel') }}: {{ data?.phone }}</span>
                        </div>
                    </div>

                    <div class="w-full flex md:justify-end gap-1">
                        <div class="w-full md:w-auto flex-col px-3 py-2 text-gray-700">
                            <div class="flex gap-2">
                                <span class="min-w-[120px] whitespace-nowrap">{{ $t('public.no') }}.</span>
                                <span class="whitespace-nowrap font-bold">{{ data?.doc_no }}</span>
                            </div>
                            <div class="flex gap-2">
                                <span class="min-w-[120px] whitespace-nowrap">{{ $t('public.date') }}</span>
                                <span class="whitespace-nowrap">{{ data?.doc_date }}</span>
                            </div>
                            <div class="flex gap-2">
                                <span class="min-w-[120px] whitespace-nowrap">{{ $t('public.terms') }}</span>
                                <span class="whitespace-nowrap">{{ data?.terms }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="w-full flex flex-col gap-1">
                <div class="w-full flex flex-col border-y border-gray-300">
                    <DataTable
                        :value="invoiceItems"
                        dataKey="id"
                        removable-sort
                    >
                        <!-- <Column field="seq" :header="'#'" class="whitespace-nowrap"/> -->
                        <Column :header="'#'" class="whitespace-nowrap">
                            <template #body="slotProps">
                                {{ slotProps.index + 1 }}
                            </template>
                        </Column>
                        <Column field="item_code" :header="$t('public.item_code')" class="whitespace-nowrap"/>
                        <Column field="description_dtl" :header="$t('public.description')" class="whitespace-nowrap"/>
                        <Column field="qty" :header="$t('public.qty')" class="whitespace-nowrap uppercase"/>
                        <Column field="uom" :header="$t('public.uom')" class="whitespace-nowrap uppercase"/>
                        <Column field="unit_price" :header="$t('public.unit_price') + ' ($)'" sortable class="whitespace-nowrap">
                            <template #body="slotProps">
                                {{ formatAmount(slotProps.data.unit_price) }}
                            </template>
                        </Column>
                        <Column field="amount" :header="$t('public.amount') + ' ($)'" sortable class="whitespace-nowrap">
                            <template #body="slotProps">
                                {{ formatAmount(slotProps.data.amount) }}
                            </template>
                        </Column>
                    </DataTable>
                </div>

                <div class="w-full flex justify-end items-center gap-1">
                    <span class="text-gray-700 font-bold">{{ $t('public.total_amount') }}:</span>
                    <span class="text-gray-700 font-bold">$ {{ formatAmount(totalAmount) }}</span>
                </div>
            </div>

        </div>
        <!-- <DataTable
            v-model:expandedRows="expandedRows"
            :value="invoiceItems"
            dataKey="id"
            removable-sort
        >
            <template #header>
                <div class="flex flex-col gap-5 items-center self-stretch mb-5">
                    <div class="flex flex-col gap-3 md:flex-row md:justify-between items-center self-stretch">
                        <div class="flex flex-col md:flex-row gap-3 items-center w-full">
                            <IconField iconPosition="left" class="relative flex items-center w-full md:w-60">
                                <CalendarIcon class="z-20 w-5 h-5 text-gray-400" />
                                <MultiSelect
                                    v-model="selectedMonths"
                                    filter
                                    :options="months"
                                    :placeholder="$t('public.month_placeholder')"
                                    :maxSelectedLabels="1"
                                    :selectedItemsLabel="`${selectedMonths.length} ${$t('public.months_selected')}`"
                                    class="w-full md:w-60 font-normal"
                                />
                            </IconField>
                            <DatePicker
                                v-model="selectedCloseDate"
                                selectionMode="range"
                                :manualInput="false"
                                :maxDate="maxDate"
                                dateFormat="dd/mm/yy"
                                showIcon
                                iconDisplay="input"
                                placeholder="yyyy/mm/dd - yyyy/mm/dd"
                                class="w-full md:w-[272px]"
                            />
                        </div>
                        <Button
                            type="button"
                            variant="primary-outlined"
                            class="w-full md:w-36"
                            @click="exportCSV($event)"
                        >
                            {{ $t('public.export') }}
                            <IconCloudDownload size="20" stroke-width="1.25" />
                        </Button>
                    </div>
                    <div class="flex md:flex-wrap md:justify-end gap-3 w-full">
                        <Button
                            type="button"
                            variant="gray-text"
                            @click="expandAll"
                        >
                            <IconPlus size="20" stroke-width="1.25" />
                            {{ $t('public.expand_all') }}
                        </Button>
                        <Button
                            type="button"
                            variant="gray-text"
                            @click="collapseAll"
                        >
                            <IconMinus size="20" stroke-width="1.25" />
                            {{ $t('public.collapse_all') }}
                        </Button>
                    </div>
                </div>
            </template>
            <Column expander style="width: 5rem" />
            <Column field="doc_date" :header="$t('public.doc_date')" sortable />
            <Column field="total_amount" :header="$t('public.total_amount') + ' ($)'">
                <template #body="slotProps">
                    {{ formatAmount(slotProps.data.total_amount) }}
                </template>
            </Column>
            <template #expansion="slotProps">
                <DataTable
                    :value="slotProps.data.invoice_items"
                    removable-sort
                >
                    <Column field="item_code" :header="$t('public.item_code')" class="whitespace-nowrap"/>
                    <Column field="account" :header="$t('public.account')" class="whitespace-nowrap"/>
                    <Column field="description_hdr" :header="$t('public.description_hdr')" class="whitespace-nowrap"/>
                    <Column field="seq" :header="$t('public.seq')" class="whitespace-nowrap"/>
                    <Column field="description_dtl" :header="$t('public.description_dtl')" class="whitespace-nowrap"/>
                    <Column field="qty" :header="$t('public.qty')" class="whitespace-nowrap"/>
                    <Column field="uom" :header="$t('public.uom')" class="whitespace-nowrap"/>
                    <Column field="unit_price" :header="$t('public.unit_price') + ' ($)'" sortable class="whitespace-nowrap">
                        <template #body="slotProps">
                            {{ formatAmount(slotProps.data.unit_price) }}
                        </template>
                    </Column>
                    <Column field="amount" :header="$t('public.amount') + ' ($)'" sortable class="whitespace-nowrap">
                        <template #body="slotProps">
                            {{ formatAmount(slotProps.data.amount) }}
                        </template>
                    </Column>
                </DataTable>
            </template>
        </DataTable> -->
    </Dialog>

</template>
