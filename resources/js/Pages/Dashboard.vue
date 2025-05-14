<script setup>
  //import Layout from '@/Layouts/GuestLayout'
  import { Head } from '@inertiajs/vue3'
  import { ref, onMounted } from 'vue';
  import { router } from '@inertiajs/vue3';
  import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
  import CreateNoteModal from '@/Components/Note/CreateNoteModal.vue'

  // State pentru notițe și filtre
  const notes = ref([]);
  const isLoading = ref(true);
  const currentFilter = ref('all');
  const searchQuery = ref('');
  const showSidebar = ref(true); // Pentru mobile
  const showCreateModal = ref(false); // State pentru modal

  // Încărcarea notițelor
  const fetchNotes = async (filter = 'all') => {
      isLoading.value = true;
      router.get(route('dashboard'), { filter: filter }, {
          preserveState: true,
          onSuccess: (page) => {
              notes.value = page.props.notes || [];
              currentFilter.value = filter;
              isLoading.value = false;
          }
      });
  };

  onMounted(() => {
    console.log('Notes received:', notes);
      fetchNotes();
  });

  // Toggle pentru sidebar pe mobile
  const toggleSidebar = () => {
      showSidebar.value = !showSidebar.value;
  };

  // Formatare dată
  const formatDate = (dateString) => {
      const date = new Date(dateString);
      return date.toLocaleDateString('ro-RO', { day: 'numeric', month: 'short', year: 'numeric' });
  };

  // Handler pentru creare notă nouă
  const handleNoteCreated = (newNote) => {
      notes.value.unshift(newNote); // Adaugă notița la începutul listei
      showCreateModal.value = false; // Închide modalul
  };

  // Deschide modalul pentru crearea unei notițe noi
  const openCreateModal = () => {
      showCreateModal.value = true;
  };
  defineProps({})
</script>

<template>
    <Head title="Notițe" />
    <AuthenticatedLayout>
      <!-- Alertă pentru utilizatorii fără telegram_id -->
      <div v-if="!$page.props.auth.user.telegram_id" class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6">
        <div class="flex">
          <div class="flex-shrink-0">
            <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
            </svg>
          </div>
          <div class="ml-3">
            <p class="text-sm text-yellow-700">
              Nu ai conectat contul Telegram. Notițele trimise prin Telegram nu vor fi salvate în contul tău.
              <a :href="route('telegram.connect')" class="font-medium underline text-yellow-700 hover:text-yellow-600">
                Conectează Telegram acum
              </a>
            </p>
          </div>
        </div>
      </div>
        <div class="flex h-screen bg-gray-50">
            <!-- Sidebar -->
            <div id="sidebar" :class="[showSidebar ? 'block' : 'hidden', 'w-64 bg-white border-r border-gray-200 h-screen sticky top-0 overflow-y-auto md:block']">
                <div class="p-4 flex items-center justify-between">
                    <div class="flex items-center">
                        <div class="w-8 h-8 rounded-md bg-gradient-to-r from-primary to-secondary flex items-center justify-center text-white font-bold">M</div>
                        <h1 class="ml-2 text-xl font-bold">MicroNote</h1>
                    </div>
                    <button @click="toggleSidebar" class="md:hidden text-gray-500 hover:text-gray-700">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </button>
                </div>

                <!-- Statistici utilizator -->
                <div class="mx-4 my-2 p-3 rounded-lg bg-gray-100">
                    <p class="text-sm font-medium">Plan Free</p>
                    <div class="mt-1 flex items-center">
                        <div class="w-full bg-gray-300 rounded-full h-2.5">
                            <div class="bg-gradient-to-r from-primary to-secondary h-2.5 rounded-full" :style="`width: ${(notes.length / 200) * 100}%`"></div>
                        </div>
                        <span class="ml-2 text-sm">{{ notes.length }}/200</span>
                    </div>
                </div>

                <!-- Buton creare notiță nouă -->
                <div class="px-4 mt-4">
                    <button
                        @click="openCreateModal"
                        class="w-full bg-gradient-to-r from-primary to-secondary hover:from-primary-dark hover:to-secondary-dark text-white py-2 px-4 rounded-lg flex items-center justify-center"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd" />
                        </svg>
                        <span class="ml-2">Notiță nouă</span>
                    </button>
                </div>

                <!-- Navigare -->
                <nav class="mt-6 px-4">
                    <div class="mb-2 text-sm font-medium text-gray-500">NAVIGARE</div>
                    <ul>
                        <li>
                            <a href="#" @click.prevent="fetchNotes('all')" :class="[currentFilter === 'all' ? 'bg-gray-200' : 'hover:bg-gray-100', 'flex items-center py-2 px-3 rounded-lg']">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-secondary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <span class="ml-2">Toate notițele</span>
                            </a>
                        </li>
                        <li>
                            <a href="#" @click.prevent="fetchNotes('favorite')" :class="[currentFilter === 'favorite' ? 'bg-gray-200' : 'hover:bg-gray-100', 'flex items-center py-2 px-3 rounded-lg']">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-secondary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                                </svg>
                                <span class="ml-2">Favorite</span>
                            </a>
                        </li>
                        <li>
                            <a href="#" @click.prevent="fetchNotes('reminder')" :class="[currentFilter === 'reminder' ? 'bg-gray-200' : 'hover:bg-gray-100', 'flex items-center py-2 px-3 rounded-lg']">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-secondary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span class="ml-2">Remindere</span>
                            </a>
                        </li>
                        <li>
                            <a href="#" @click.prevent="fetchNotes('shopping_list')" :class="[currentFilter === 'shopping_list' ? 'bg-gray-200' : 'hover:bg-gray-100', 'flex items-center py-2 px-3 rounded-lg']">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-secondary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                                </svg>
                                <span class="ml-2">Liste de Cumpărături</span>
                            </a>
                        </li>
                    </ul>
                </nav>

                <!-- Tag-uri -->
                <div class="mt-6 px-4">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-gray-500">TAG-URI</span>
                        <button class="text-gray-500 hover:text-gray-700">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                        </button>
                    </div>
                    <ul>
                        <li>
                            <a href="#" @click.prevent="fetchNotes('task')" :class="[currentFilter === 'task' ? 'bg-gray-200' : 'hover:bg-gray-100', 'flex items-center py-2 px-3 rounded-lg']">
                                <span class="w-3 h-3 rounded-full bg-red-500"></span>
                                <span class="ml-2">Task</span>
                            </a>
                        </li>
                        <li>
                            <a href="#" @click.prevent="fetchNotes('idea')" :class="[currentFilter === 'idea' ? 'bg-gray-200' : 'hover:bg-gray-100', 'flex items-center py-2 px-3 rounded-lg']">
                                <span class="w-3 h-3 rounded-full bg-blue-500"></span>
                                <span class="ml-2">Idee</span>
                            </a>
                        </li>
                        <li>
                            <a href="#" @click.prevent="fetchNotes('shopping_list')" :class="[currentFilter === 'shopping_list' ? 'bg-gray-200' : 'hover:bg-gray-100', 'flex items-center py-2 px-3 rounded-lg']">
                                <span class="w-3 h-3 rounded-full bg-green-500"></span>
                                <span class="ml-2">Cumpărături</span>
                            </a>
                        </li>
                    </ul>
                </div>

                <!-- Footer -->
                <div class="p-4 mt-8 border-t border-gray-200">
                    <div class="flex items-center justify-between">
                        <button class="text-gray-500 hover:text-gray-700">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                            </svg>
                        </button>
                        <button class="text-gray-500 hover:text-gray-700">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </button>
                        <a href="#" class="text-blue-500 hover:text-blue-600 text-sm font-medium">
                            Upgrade
                        </a>
                    </div>
                </div>
            </div>

            <!-- Conținut principal -->
            <div class="flex-1 flex flex-col min-h-screen overflow-hidden">
                <!-- Header -->
                <header class="bg-white border-b border-gray-200 p-4 flex items-center justify-between">
                    <div class="flex items-center">
                        <button @click="toggleSidebar" class="md:hidden mr-4 text-gray-500 hover:text-gray-700">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M3 5a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 10a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 15a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd" />
                            </svg>
                        </button>
                        <h2 class="text-lg font-semibold">{{ currentFilter === 'all' ? 'Toate notițele' : (currentFilter === 'favorite' ? 'Favorite' : (currentFilter === 'reminder' ? 'Remindere' : currentFilter)) }}</h2>
                    </div>
                    <div class="flex items-center space-x-3">
                        <button class="flex items-center text-sm bg-gray-100 hover:bg-gray-200 text-gray-700 py-1 px-3 rounded">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                            </svg>
                            Export CSV
                        </button>
                        <div class="relative bg-gray-100 rounded-lg">
                            <input
                                v-model="searchQuery"
                                type="text"
                                placeholder="Caută..."
                                class="py-2 pl-9 pr-4 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-gray-100 w-40 md:w-64"
                            />
                            <svg xmlns="http://www.w3.org/2000/svg" class="absolute left-3 top-3 text-gray-500 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                    </div>
                </header>

                <!-- Conținut principal -->
                <main class="flex-1 p-4 overflow-auto">
                    <!-- Filtre și sortare -->
                    <div class="mb-4 flex flex-wrap items-center gap-2">
                        <span class="text-sm text-gray-500">Filtrează:</span>
                        <button @click="fetchNotes('all')" :class="[currentFilter === 'all' ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 hover:bg-gray-200 text-gray-700', 'py-1 px-3 rounded text-sm']">
                            Toate
                        </button>
                        <button @click="fetchNotes('task')" :class="[currentFilter === 'task' ? 'bg-red-100 text-red-700' : 'bg-gray-100 hover:bg-gray-200 text-gray-700', 'py-1 px-3 rounded text-sm flex items-center']">
                            <span class="w-2 h-2 rounded-full bg-red-500 mr-1"></span>
                            Task
                        </button>
                        <button @click="fetchNotes('idea')" :class="[currentFilter === 'idea' ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 hover:bg-gray-200 text-gray-700', 'py-1 px-3 rounded text-sm flex items-center']">
                            <span class="w-2 h-2 rounded-full bg-blue-500 mr-1"></span>
                            Idee
                        </button>
                        <button @click="fetchNotes('shopping_list')" :class="[currentFilter === 'shopping_list' ? 'bg-green-100 text-green-700' : 'bg-gray-100 hover:bg-gray-200 text-gray-700', 'py-1 px-3 rounded text-sm flex items-center']">
                            <span class="w-2 h-2 rounded-full bg-green-500 mr-1"></span>
                            Cumpărături
                        </button>
                        <div class="ml-auto">
                            <button class="flex items-center text-sm bg-gray-100 hover:bg-gray-200 text-gray-700 py-1 px-3 rounded">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h13M3 8h9m-9 4h9m5-4v12m0 0l-4-4m4 4l4-4" />
                                </svg>
                                Cele mai noi
                            </button>
                        </div>
                    </div>

                    <!-- Loading indicator -->
                    <div v-if="isLoading" class="flex justify-center items-center h-64">
                        <svg class="animate-spin h-8 w-8 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>

                    <!-- No notes message -->
                    <div v-else-if="notes.length === 0" class="flex flex-col items-center justify-center h-64">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                        <p class="mt-4 text-gray-500">Nu există notițe pentru acest filtru.</p>
                        <button class="mt-4 px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600">
                            Adaugă prima ta notiță
                        </button>
                    </div>

                    <!-- Notes Grid -->
                    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <!-- Note Card - Task -->
                        <div v-for="(note, index) in notes" :key="note?.id || index" v-if="notes" class="p-4 rounded-lg bg-white border border-gray-200 hover:shadow-md transition-shadow cursor-pointer">
                            <div class="flex items-center justify-between mb-2">
                                <div class="flex items-center">
                                    <span :class="[
                                        'w-3 h-3 rounded-full',
                                        note.note_type === 'task' ? 'bg-red-500' :
                                        note.note_type === 'idea' ? 'bg-blue-500' :
                                        note.note_type === 'shopping_list' ? 'bg-green-500' : 'bg-gray-500'
                                    ]"></span>
                                    <span class="ml-2 text-xs font-medium text-gray-500">{{
                                            note.note_type === 'task' ? 'Task' :
                                                note.note_type === 'idea' ? 'Idee' :
                                                    note.note_type === 'shopping_list' ? 'Cumpărături' : 'Notiță'
                                        }}</span>
                                </div>
                                <svg v-if="note.is_favorite" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-yellow-500 fill-yellow-500" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                </svg>
                            </div>

                            <h3 class="font-medium mb-2">{{ note.title || note.content.substring(0, 40) }}</h3>
                            <p class="text-sm text-gray-600 mb-2">{{ note.content }}</p>

                            <!-- Shopping list content -->
                            <div v-if="note.note_type === 'shopping_list' && note.metadata && note.metadata.items" class="mb-2">
                                <div v-for="(item, index) in note.metadata.items" :key="index" class="flex items-center mb-1">
                                    <div :class="['w-4 h-4 rounded border', item.completed ? 'border-green-500 bg-green-500' : 'border-gray-300']" class="flex items-center justify-center">
                                        <svg v-if="item.completed" xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 text-white" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                    <span :class="['ml-2 text-sm', item.completed ? 'line-through text-gray-500' : '']">{{ item.text }}</span>
                                </div>
                            </div>

                            <div class="flex justify-between items-center mt-3 text-xs">
                                <span class="text-gray-500">{{ formatDate(note.created_at) }}</span>
                                <div v-if="note.metadata && note.metadata.reminder_at" class="flex items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span class="ml-1 text-blue-500">{{ formatDate(note.metadata.reminder_at) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </main>
            </div>
        </div>
    </AuthenticatedLayout>
    <CreateNoteModal
        :show="showCreateModal"
        @close="showCreateModal = false"
        @noteCreated="handleNoteCreated"
    />
</template>

<style>
/* Tema de culori personalizată */
:root {
    --color-primary: #10b981;
    --color-primary-light: #4ade80;
    --color-primary-dark: #059669;
    --color-secondary: #38bdf8;
    --color-secondary-light: #7dd3fc;
    --color-secondary-dark: #0284c7;
    --color-accent: #0ea5e9;
}

.bg-primary {
    background-color: var(--color-primary);
}

.bg-primary-light {
    background-color: var(--color-primary-light);
}

.bg-secondary {
    background-color: var(--color-secondary);
}

.from-primary {
    --tw-gradient-from: var(--color-primary);
    --tw-gradient-stops: var(--tw-gradient-from), var(--tw-gradient-to, rgba(16, 185, 129, 0));
}

.to-secondary {
    --tw-gradient-to: var(--color-secondary);
}

.text-primary {
    color: var(--color-primary);
}

.text-secondary {
    color: var(--color-secondary);
}

.text-accent {
    color: var(--color-accent);
}

/* Stiluri pentru scrollbar personalizat */
::-webkit-scrollbar {
    width: 6px;
}

::-webkit-scrollbar-track {
    background: #f1f1f1;
}

::-webkit-scrollbar-thumb {
    background: #d1d5db;
    border-radius: 3px;
}

::-webkit-scrollbar-thumb:hover {
    background: #a3a3a3;
}
</style>
