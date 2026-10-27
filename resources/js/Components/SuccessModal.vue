<script setup>
import { onMounted, ref, watch } from 'vue';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    title: {
        type: String,
        default: 'Success!',
    },
    message: {
        type: String,
        default: 'Perubahan berhasil disimpan.',
    },
    buttonLabel: {
        type: String,
        default: 'Done',
    },
});

const emit = defineEmits(['close']);
const modalVisible = ref(false);
const coinSuccessAnimation = '/animation/coin-success.gif';

watch(
    () => props.show,
    (show) => {
        modalVisible.value = show;
    },
);

onMounted(() => {
    modalVisible.value = props.show;
});
</script>

<template>
    <Modal
        :show="modalVisible"
        :closeable="false"
        max-width="sm"
        :label="title"
    >
        <div class="flex flex-col items-center px-6 pb-6 pt-8 text-center sm:px-8">
            <img
                :src="coinSuccessAnimation"
                alt=""
                class="h-40 w-40 object-contain"
            />

            <h2 class="mt-3 text-2xl font-bold text-[#172B4D]">
                {{ title }}
            </h2>
            <p class="mt-2 max-w-xs text-sm leading-6 text-[#637083]">
                {{ message }}
            </p>

            <ButtonPrimary
                type="button"
                class="mt-7 w-full"
                @click="emit('close')"
            >
                {{ buttonLabel }}
            </ButtonPrimary>
        </div>
    </Modal>
</template>
