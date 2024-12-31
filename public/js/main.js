document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('combat-form');
    const addParty1Btn = document.getElementById('add-party1');
    const addParty2Btn = document.getElementById('add-party2');
    const addDefaultParty1Btn = document.getElementById('add-default-party1');
    const party1Container = document.getElementById('party1-container');
    const party2Container = document.getElementById('party2-container');
    const resultDiv = document.getElementById('result');

    const DEFAULT_PARTY = [
        { name: "Aedan", hp: 8, ac: 17, attackBonus: 1, damageDice: "1d6" },
        { name: "Inathyaa", hp: 3, ac: 16, attackBonus: 0, damageDice: "1d4" },
        { name: "Varek", hp: 7, ac: 12, attackBonus: 2, damageDice: "1d10" },
        { name: "Ragnar", hp: 5, ac: 12, attackBonus: 0, damageDice: "1d4" },
        { name: "Joker", hp: 3, ac: 13, attackBonus: 2, damageDice: "1d6" }
    ];

    const createCharacterFields = (type) => {
        const div = document.createElement('div');
        div.className = 'character-entry';
        div.innerHTML = `
            <button type="button" class="remove-character">X</button>
            <input type="text" name="${type}[name][]" placeholder="Nom" required>
            <input type="number" name="${type}[hp][]" placeholder="PV" required>
            <input type="number" name="${type}[ac][]" placeholder="CA" required>
            <input type="number" name="${type}[attackBonus][]" placeholder="Bonus Attaque" value="0" required>
            <input type="text" name="${type}[damageDice][]" placeholder="Dégâts (ex: 1d8)" pattern="\\d+d\\d+" required>
            ${type === 'party2' ? `
                <input type="number" name="${type}[moral][]" placeholder="Moral (2-12)" min="2" max="12" required>
                <input type="number" name="${type}[count][]" placeholder="Nombre" value="1" min="1" required>
            ` : ''}
        `;
        return div;
    };

    const loadSavedData = () => {
        const savedData = localStorage.getItem('combatData');
        if (savedData) {
            const data = JSON.parse(savedData);
            // Recréer party1
            data.party1?.forEach(character => {
                const div = createCharacterFields('party1');
                const inputs = div.querySelectorAll('input');
                inputs[0].value = character.name;
                inputs[1].value = character.hp;
                inputs[2].value = character.ac;
                inputs[3].value = character.attackBonus;
                inputs[4].value = character.damageDice;
                party1Container.appendChild(div);
            });
            // Recréer party2
            data.party2?.forEach(character => {
                const div = createCharacterFields('party2');
                const inputs = div.querySelectorAll('input');
                inputs[0].value = character.name;
                inputs[1].value = character.hp;
                inputs[2].value = character.ac;
                inputs[3].value = character.attackBonus;
                inputs[4].value = character.damageDice;
                inputs[5].value = character.moral;
                inputs[6].value = character.count;
                party2Container.appendChild(div);
            });
        }
    };

    addParty1Btn.addEventListener('click', () => {
        party1Container.appendChild(createCharacterFields('party1'));
    });

    addParty2Btn.addEventListener('click', () => {
        party2Container.appendChild(createCharacterFields('party2'));
    });

    addDefaultParty1Btn.addEventListener('click', () => {
        DEFAULT_PARTY.forEach(character => {
            const div = createCharacterFields('party1');
            const inputs = div.querySelectorAll('input');
            inputs[0].value = character.name;
            inputs[1].value = character.hp;
            inputs[2].value = character.ac;
            inputs[3].value = character.attackBonus;
            inputs[4].value = character.damageDice;
            party1Container.appendChild(div);
        });
    });

    document.addEventListener('click', (e) => {
        if (e.target.className === 'remove-character') {
            e.target.parentElement.remove();
        }
    });

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(form);

        // Transformer FormData en structure de données
        const data = {
            party1: {
                name: [],
                hp: [],
                ac: [],
                attackBonus: [],
                damageDice: []
            },
            party2: {
                name: [],
                hp: [],
                ac: [],
                attackBonus: [],
                damageDice: [],
                moral: [],
                count: []
            }
        };



        for (let [key, value] of formData.entries()) {
            const matches = key.match(/party(\d+)\[(\w+)\]/);
            if (matches) {
                const [, party, field] = matches;
                data[`party${party}`][field].push(['hp', 'ac', 'attackBonus', 'moral', 'count'].includes(field)
                    ? parseInt(value, 10)
                    : value);
            }
        }

        // Transformer en format attendu par l'API
        const party1 = data.party1.name.map((_, index) => ({
            name: data.party1.name[index],
            hp: data.party1.hp[index],
            ac: data.party1.ac[index],
            attackBonus: data.party1.attackBonus[index],
            damageDice: data.party1.damageDice[index]
        }));

        const party2 = data.party2.name.map((_, index) => ({
            name: data.party2.name[index],
            hp: data.party2.hp[index],
            ac: data.party2.ac[index],
            attackBonus: data.party2.attackBonus[index],
            damageDice: data.party2.damageDice[index],
            moral: data.party2.moral[index],
            count: data.party2.count[index]
        }));

        localStorage.setItem('combatData', JSON.stringify({party1, party2}));

        try {
            const response = await fetch('/api/simulate.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    party1,
                    party2,
                    useMorale: document.getElementById('use-morale').checked
                })
            });

            const result = await response.json();
            if (result.error) {
                throw new Error(result.error);
            }

            let logHtml = '';
            if (result.victoryLog) {
                logHtml += '<h4>Exemple de victoire:</h4><ul>';
                result.victoryLog.forEach(line => {
                    logHtml += `<li>${line}</li>`;
                });
                logHtml += '</ul>';
            }
            if (result.defeatLog) {
                logHtml += '<h4>Exemple de défaite:</h4><ul>';
                result.defeatLog.forEach(line => {
                    logHtml += `<li>${line}</li>`;
                });
                logHtml += '</ul>';
            }

            resultDiv.innerHTML = `
                <h3>Résultats après ${result.totalSimulations} simulations:</h3>
                <p>Victoires Groupe 1: ${result.party1Victories} (${result.party1WinRate.toFixed(1)}%)</p>
                <p>Victoires Groupe 2: ${result.party2Victories} (${result.party2WinRate.toFixed(1)}%)</p>
                <h4>Morts par personnage:</h4>
                <ul>
                ${Object.entries(result.deathStats)
                .sort((a, b) => b[1] - a[1])
                .map(([name, deaths]) => `<li>${name}: ${deaths} morts (${(deaths/result.totalSimulations*100).toFixed(1)}%)</li>`)
                .join('')}
                </ul>
                ${logHtml}
            `;
        } catch (error) {
            resultDiv.innerHTML = `<p class="error">Erreur: ${error.message}</p>`;
        }
    });

    loadSavedData();
});