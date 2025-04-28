<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Invoice',
        href: '/invoice',
    },
];

const fileInput = ref<HTMLInputElement | null>(null);
const selectedFileName = ref<string | null>(null);

const triggerFileInput = () => {
    fileInput.value?.click();
};

const handleFileUpload = (event: Event) => {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];
    if (!file) return;

    selectedFileName.value = file.name;

    const formData = new FormData();
    formData.append('file', file);

    router.post('/invoices/upload', formData, {
        preserveScroll: true,
        onSuccess: () => {
            console.log('File uploaded!');
        },
        onError: (errors) => {
            console.error(errors);
        },
    });
};
</script>

<template>
    <Head title="Invoice Submit" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <!-- 👇 File Upload Area -->
            <div class="flex flex-col gap-2">
                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
                    Upload Excel File (.csv, .xls, .xlsx, .ods)
                </label>

                <!-- Hidden file input -->
                <input
                    ref="fileInput"
                    type="file"
                    accept=".csv,.xls,.xlsx,.ods"
                    class="hidden"
                    @change="handleFileUpload"
                />

                <!-- Trigger button -->
                <button
                    type="button"
                    @click="triggerFileInput"
                    class="inline-flex items-center justify-center rounded-md bg-primary px-4 py-2 text-sm font-medium text-white hover:bg-primary-dark"
                >
                    Upload File
                </button>

                <!-- Show selected file name -->
                <span class="text-sm text-gray-600 dark:text-gray-400">
                    Selected file: <span class="font-medium">{{ selectedFileName }}</span>
                </span>
            </div>
        </div>
    </AppLayout>
</template>
