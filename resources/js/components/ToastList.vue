<script setup lang="ts">
import ToastListItem from "@/components/ToastListItem.vue";
import { onUnmounted } from "vue";
import { Inertia } from "@inertiajs/inertia";
import { usePage } from "@inertiajs/vue3";
import toast from "@/composables/toast";

interface ToastPayload {
    title: string;
    message: string;
    type: 'success' | 'info' | 'warning' | 'error';
}

const page = usePage();

const removeFinishEventListener = Inertia.on("finish", () => {
    const toastPayload = page.props.toast as ToastPayload | undefined;

    if (toastPayload) {
        toast.add({
            title: toastPayload.title,
            message: toastPayload.message,
            type: toastPayload.type,
        });
    }
});

onUnmounted(() => removeFinishEventListener());

function remove(index: number) {
    toast.remove(index);
}
</script>
<template>
    <TransitionGroup
        tag="div"
        enter-from-class="-translate-y-full opacity-0"
        enter-active-class="duration-300"
        leave-active-class="duration-300"
        leave-to-class="-translate-y-full opacity-0"
        class="fixed top-4 left-1/2 z-50 min-w-[320px] w-full max-w-[640px] -translate-x-2/4 space-y-4">
        <ToastListItem
            v-for="(item, index) in toast.items"
            :key="item.key"
            :title="item.title"
            :message="item.message"
            :type="item.type"
            @remove="remove(index)"
        />
    </TransitionGroup>
</template>
