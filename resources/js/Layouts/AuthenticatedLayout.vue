<script setup>
import { ref } from 'vue';

const showingNavigationDropdown = ref(false);
</script>

<template>
    <div>
            <main>
                <slot />
            </main>
    </div>
</template>
