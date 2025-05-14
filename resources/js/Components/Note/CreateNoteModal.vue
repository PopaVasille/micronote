<script setup>
import {ref} from 'vue';
import axios from "axios";

const props = defineProps({
    show: Boolean,
});

const emit = defineEmits(['close','noteCreated']);

const form = ref({
    title: '',
    content: '',
    note_type: 'simple',
    is_favorite: false,
});

const errors = ref({});
const isSubmitting = ref(false);

const closeModal = () => {
    emit('close');
    resetForm();
};

const resetForm = () => {
    form.value = {
        title: '',
        content: '',
        note_type: 'simple',
        is_favorite: false,
    };
    errors.value = {};
}

const createNote = async () => {
    isSubmitting.value = true;
    errors.value = {};

    try{
        const response = await axios.post('/api/notes', form.value);
        emit('noteCreated', response.data.data);
        closeModal();
    }catch(error){
        if (error.response && error.response.data && error.response.data.errors) {
            errors.value = error.response.data.errors;
        } else {
            alert('A apărut o eroare la crearea notiței. Încearcă din nou.');
        }
    } finally {
        isSubmitting.value = false;
    }
};
</script>
<template>
    <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto">
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-black opacity-50" @click="closeModal"></div>

        <!-- Modal -->
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="relative w-full max-w-md mx-auto bg-white rounded-lg shadow-xl">
                <!-- Header -->
                <div class="flex items-center justify-between p-4 border-b">
                    <h3 class="text-lg font-semibold">Notiță nouă</h3>
                    <button @click="closeModal" class="text-gray-500 hover:text-gray-700">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </button>
                </div>

                <!-- Body -->
                <div class="p-4">
                    <form @submit.prevent="createNote">
                        <!-- Titlu -->
                        <div class="mb-4">
                            <label for="title" class="block text-sm font-medium text-gray-700 mb-1">Titlu (opțional)</label>
                            <input
                                type="text"
                                id="title"
                                v-model="form.title"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                placeholder="Adaugă un titlu"
                            >
                            <p v-if="errors.title" class="mt-1 text-sm text-red-600">{{ errors.title[0] }}</p>
                        </div>

                        <!-- Conținut -->
                        <div class="mb-4">
                            <label for="content" class="block text-sm font-medium text-gray-700 mb-1">Conținut</label>
                            <textarea
                                id="content"
                                v-model="form.content"
                                rows="4"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                placeholder="Scrie aici notița ta..."
                                required
                            ></textarea>
                            <p v-if="errors.content" class="mt-1 text-sm text-red-600">{{ errors.content[0] }}</p>
                        </div>

                        <!-- Tip notiță -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tip notiță</label>
                            <div class="flex flex-wrap space-x-2">
                                <label class="flex items-center space-x-2 border rounded-md px-3 py-2 my-2 cursor-pointer" :class="{ 'border-blue-500 bg-blue-50': form.note_type === 'simple' }">
                                    <input type="radio" v-model="form.note_type" value="simple" class="hidden">
                                    <span class="w-3 h-3 rounded-full bg-gray-500"></span>
                                    <span>Simplă</span>
                                </label>

                                <label class="flex items-center space-x-2 border rounded-md px-3 py-2 my-2 cursor-pointer" :class="{ 'border-red-500 bg-red-50': form.note_type === 'task' }">
                                    <input type="radio" v-model="form.note_type" value="task" class="hidden">
                                    <span class="w-3 h-3 rounded-full bg-red-500"></span>
                                    <span>Task</span>
                                </label>

                                <label class="flex items-center space-x-2 border rounded-md px-3 py-2 my-2 cursor-pointer" :class="{ 'border-blue-500 bg-blue-50': form.note_type === 'idea' }">
                                    <input type="radio" v-model="form.note_type" value="idea" class="hidden">
                                    <span class="w-3 h-3 rounded-full bg-blue-500"></span>
                                    <span>Idee</span>
                                </label>

                                <label class="flex items-center space-x-2 border rounded-md px-3 py-2 cursor-pointer" :class="{ 'border-green-500 bg-green-50': form.note_type === 'shopping_list' }">
                                    <input type="radio" v-model="form.note_type" value="shopping_list" class="hidden">
                                    <span class="w-3 h-3 rounded-full bg-green-500"></span>
                                    <span>Cumpărături</span>
                                </label>
                                <label class="flex items-center space-x-2 border rounded-md px-3 py-2 cursor-pointer" :class="{ 'border-green-500 bg-green-50': form.note_type === 'shopping_list' }">
                                    <input type="radio" v-model="form.note_type" value="shopping_list" class="hidden">
                                    <span class="w-3 h-3 rounded-full bg-green-500"></span>
                                    <span>Cumpărături2</span>
                                </label>
                            </div>
                        </div>

                        <!-- Favorite checkbox -->
                        <div class="mb-4">
                            <label class="flex items-center space-x-2 cursor-pointer">
                                <input type="checkbox" v-model="form.is_favorite" class="w-4 h-4 text-blue-500 border-gray-300 rounded focus:ring-blue-500">
                                <span>Marchează ca favorit</span>
                            </label>
                        </div>
                    </form>
                </div>

                <!-- Footer -->
                <div class="p-4 border-t flex justify-end space-x-2">
                    <button @click="closeModal" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300">
                        Anulează
                    </button>
                    <button
                        @click="createNote"
                        :disabled="isSubmitting"
                        class="px-4 py-2 bg-gradient-to-r from-primary to-secondary text-white rounded-md hover:from-primary-dark hover:to-secondary-dark flex items-center"
                    >
                        <svg v-if="isSubmitting" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Salvează notiță
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
