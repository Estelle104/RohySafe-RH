<script setup>
    import{useRouter}from'vue-router';const router=useRouter()
</script>
<template>
    <main>
        <section>
            <p>Entretien créé</p>
            <button @click="router.push('/candidatures')">Retour aux candidatures</button>
        </section>
    </main>
</template>
<style scoped>main{min-height:100vh;background:#f4f7f7;padding:80px 16px}section{max-width:600px;margin:auto;background:#e0f4ee;padding:30px;border-radius:16px;color:#00534f}button{background:teal;color:#fff;border:0;border-radius:8px;padding:11px 15px;font-weight:bold}</style>
