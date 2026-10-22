const { createApp } = Vue;



const formulaire = {
    template: `
        <form id="formulaire" @submit.prevent="soumettre">
            <label>
                Titre : <input type="text" name="titre" v-model="titre" required>
            </label>
            <label>
                Description : <textarea name="description" v-model="description"></textarea>
            </label>
            <label>
                Niveau :
                <select v-model="niveau" name="niveau" required>
                    <option value="">Choisir</option>
                    <option value="L1">L1</option>
                    <option value="L2">L2</option>
                    <option value="L3">L3</option>
                </select>
            </label>
            <button>Ajouter</button>
        </form>
    `

}
createApp({
    components: { formulaire }

    
}).mount('#app');

const form = document.querySelector('#formulaire');

form.addEventListener('submit', async (e) => {
    e.preventDefault();
    
const data = new FormData(form);
    const reponse = await fetch('http://localhost:8000/api/formations', {
        method: 'POST',
        body: JSON.stringify(Object.fromEntries(data)),
        headers: { 'Content-Type': 'application/json' }
    });

    if (!reponse.ok) {
        alert('Erreur : ' + reponse.status);
        return;
    }

    const formation = await reponse.json();
    console.log('Formation ajoutée :', formation);
});