<script setup>
import ErrorPage from '@/Components/ErrorPage.vue';
</script>

<template>
    <ErrorPage
        code="403"
        title="Not Allowed"
        message="You don’t have permission to view this page."
    />
</template>
