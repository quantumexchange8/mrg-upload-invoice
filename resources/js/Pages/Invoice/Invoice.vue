<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { ref, h, watch, onMounted, computed } from "vue";
import { usePage, useForm } from "@inertiajs/vue3";
import { trans, wTrans } from "laravel-vue-i18n";
import InvoiceListing from "@/Pages/Invoice/Partials/InvoiceListing.vue";
import InvoiceSubmit from "@/Pages/Invoice/Partials/InvoiceSubmit.vue";

</script>

<template>
    <AuthenticatedLayout :title="$t('public.invoice')">
        <div class="flex flex-col items-center gap-5 self-stretch">
            <InvoiceSubmit />
            <InvoiceListing />
        </div>
    </AuthenticatedLayout>

</template>
