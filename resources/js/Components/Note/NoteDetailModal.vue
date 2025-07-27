<script setup>
import { ref, watch, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';

const props = defineProps({
    show: Boolean,
    note: Object,
});

const emit = defineEmits(['close', 'noteUpdated']);

const isEditing = ref(false);

// Folosim useForm de la Inertia pentru a gestiona starea formularului de editare
const form = useForm({
    id: null,
    title: '',
    content: '',
});

// Când prop-ul `note` se schimbă (când se deschide modalul), actualizăm formularul
watch(() => props.note, (newNote) => {
    if (newNote) {
        form.id = newNote.id;
        form.title = newNote.title;
        form.content = newNote.content;
    } else {
        form.reset();
        isEditing.value = false;
    }
});

const startEditing = () => {
    isEditing.value = true;
};

const cancelEditing = () => {
    // Resetează formularul la valorile originale ale notiței
    form.reset();
    isEditing.value = false;
};

const closeModal = () => {
    emit('close');
};

// Funcția de salvare
const saveChanges = () => {
    if (!props.note) return;

    // Trimitem cererea de actualizare către backend
    form.post(route('notes.update', props.note.id), {
        preserveScroll: true,
        onSuccess: () => {
            // Tot ce trebuie să facem la succes este să închidem modalul.
            // Lista de notițe din Dashboard se va actualiza automat.
            closeModal();
        },
        onError: (errors) => {
            console.error('Update failed:', errors);
            // Poți afișa erorile utilizatorului aici dacă dorești.
        }
    });
};

</script>

<template>
    <Modal :show="show" @close="closeModal">
        <div class="p-6">
            <div v-if="!isEditing && note">
                <h2 class="text-2xl font-bold text-gray-900 mb-4">{{ note.title || 'Notiță fără titlu' }}</h2>
                <p class="text-gray-700 whitespace-pre-wrap">{{ note.content }}</p>
                <div class="mt-4 text-sm text-gray-500">
                    Creat la: {{ new Date(note.created_at).toLocaleString('ro-RO') }}
                </div>
            </div>

            <form v-if="isEditing && note" @submit.prevent="saveChanges">
                <h2 class="text-2xl font-bold text-gray-900 mb-4">Editare Notiță</h2>
                <div>
                    <InputLabel for="title" value="Titlu" />
                    <TextInput id="title" type="text" class="mt-1 block w-full" v-model="form.title" />
                    <p v-if="form.errors.title" class="text-sm text-red-600 mt-1">{{ form.errors.title }}</p>
                </div>
                <div class="mt-4">
                    <InputLabel for="content" value="Conținut" />
                    <textarea id="content" rows="6" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" v-model="form.content"></textarea>
                    <p v-if="form.errors.content" class="text-sm text-red-600 mt-1">{{ form.errors.content }}</p>
                </div>
            </form>

            <div class="mt-6 flex justify-end space-x-2">
                <SecondaryButton @click="closeModal">Închide</SecondaryButton>

                <template v-if="!isEditing">
                    <PrimaryButton @click="startEditing">Editare</PrimaryButton>
                </template>

                <template v-else>
                    <SecondaryButton @click="cancelEditing">Anulează</SecondaryButton>
                    <PrimaryButton type="submit" @click="saveChanges" :disabled="form.processing">
                        Salvează
                    </PrimaryButton>
                </template>
            </div>
        </div>
    </Modal>
</template>
