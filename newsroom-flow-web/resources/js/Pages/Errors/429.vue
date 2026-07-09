<script setup>
import ErrorPage from '@/Components/ErrorPage.vue';
</script>

<template>
    <ErrorPage
        code="429"
        title="Slow Down a Moment"
        message="You’ve made a lot of requests in a short time. Please wait a bit and try again."
    />
</template>
