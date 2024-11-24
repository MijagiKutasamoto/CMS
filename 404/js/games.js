window.onload = function() {
    const canvas = document.getElementById('game-board');
    const ctx = canvas.getContext('2d');
    const gridSize = 20;
    const gridCount = canvas.width / gridSize;
    let player = { x: 1, y: 1 };
    const modal = document.getElementById('battle-modal');
    const modalTitle = document.getElementById('modal-title');
    const pokemonInfo = document.getElementById('pokemon-info');
    const battleStatus = document.getElementById('battle-status');
    const attackButton = document.getElementById('attack-button');
    const catchButton = document.getElementById('catch-button');
    const runButton = document.getElementById('run-button');
    const defaultPokemonSelect = document.getElementById('default-pokemon-select');
    const setDefaultButton = document.getElementById('set-default-button');
	const saveButton = document.getElementById('save-to-json');
    const loadInput = document.getElementById('load-json-file');
    let wildPokemon = null;
    let pokemonList = {
        standard: [],
        legendary: [],
        mythical: [],
        mega_evolutions: []
    };
    let defaultPokemon = null;
    const map = generateMap();

    // Wczytanie listy Pokémonów z pliku JSON
    fetch('kanto_pokemon.json')
        .then(response => {
            if (!response.ok) {
                throw new Error('Nie udało się załadować pliku JSON');
            }
            return response.json();
        })
        .then(data => {
            console.log('Załadowano listę Pokémonów:', data);
            pokemonList = data;
            initializeFirstPokemon();
        })
        .catch(error => {
            console.error('Błąd podczas ładowania pliku JSON:', error);
        });

    function getRandomStat() {
        return Math.floor(Math.random() * 31) + 5; // Statystyki w zakresie od 5 do 35
    }

    function createRandomPokemon() {
        let chosenList;
        const randomChance = Math.random();

        // Ustawienie szansy na wybranie grupy Pokémonów
        if (randomChance < 0.00001) {
            chosenList = pokemonList.mythical; // 0.00001% szansy na mistyczne
        } else if (randomChance < 0.00009) {
            chosenList = pokemonList.legendary; // 0.00009% szansy na legendarne
        } else if (randomChance < 0.0009) {
            chosenList = pokemonList.mega_evolutions; // 0.0009% szansy na mega ewolucje
        } else {
            chosenList = pokemonList.standard; // reszta na standardowe
        }

        if (!chosenList || chosenList.length === 0) {
            console.warn('Wybrana grupa Pokémonów jest pusta!');
            return null;
        }

        const randomIndex = Math.floor(Math.random() * chosenList.length);
        const selectedPokemon = chosenList[randomIndex];
        const level = Math.floor(Math.random() * 50) + 1; // Poziom od 1 do 50

        return {
            name: selectedPokemon.name,
            icon: selectedPokemon.icon,
            attack: getRandomStat(),
            defense: getRandomStat(),
            level: level
        };
    }

    function initializeFirstPokemon() {
        let caughtPokemon = JSON.parse(localStorage.getItem('caughtPokemon')) || [];
        if (caughtPokemon.length === 0) {
            const firstPokemon = createRandomPokemon();
            if (firstPokemon) {
                console.log('Wylosowano pierwszego Pokémona:', firstPokemon);
                caughtPokemon.push(firstPokemon);
                localStorage.setItem('caughtPokemon', JSON.stringify(caughtPokemon));
                displayCaughtPokemon();
                updatePokemonSelect();
            }
        }
    }

    function savePokemonToStorage(pokemon) {
        let caughtPokemon = JSON.parse(localStorage.getItem('caughtPokemon')) || [];
        caughtPokemon.push(pokemon);
        localStorage.setItem('caughtPokemon', JSON.stringify(caughtPokemon));
        displayCaughtPokemon();
        updatePokemonSelect();
    }

    function displayCaughtPokemon() {
        let caughtPokemon = JSON.parse(localStorage.getItem('caughtPokemon')) || [];
        const pokemonStorage = document.getElementById('pokemon-storage');
        pokemonStorage.innerHTML = ''; // Wyczyść listę przed jej zaktualizowaniem
        caughtPokemon.forEach((pokemon, index) => {
            let listItem = document.createElement('li');
            listItem.innerHTML = `
                <img src="${pokemon.icon}" alt="${pokemon.name}" width="50"> 
                ${pokemon.name} (Poziom: ${pokemon.level}, Atak: ${pokemon.attack}, Obrona: ${pokemon.defense})
                <button onclick="transferPokemon(${index})">Transfer</button>
            `;
            pokemonStorage.appendChild(listItem);
        });
    }

    function updatePokemonSelect() {
        let caughtPokemon = JSON.parse(localStorage.getItem('caughtPokemon')) || [];
        defaultPokemonSelect.innerHTML = '<option value="">Wybierz domyślnego Pokémona</option>';
        caughtPokemon.forEach((pokemon, index) => {
            let option = document.createElement('option');
            option.value = index;
            option.textContent = `${pokemon.name} (Poziom: ${pokemon.level})`;
            defaultPokemonSelect.appendChild(option);
        });
    }

    setDefaultButton.onclick = function() {
        const selectedIndex = defaultPokemonSelect.value;
        if (selectedIndex !== "") {
            let caughtPokemon = JSON.parse(localStorage.getItem('caughtPokemon')) || [];
            defaultPokemon = caughtPokemon[selectedIndex];
            console.log(`Ustawiono domyślnego Pokémona do walki: ${defaultPokemon.name}`);
            alert(`Ustawiono domyślnego Pokémona: ${defaultPokemon.name}`);
        } else {
            alert('Wybierz Pokémona z listy, aby ustawić go jako domyślnego.');
        }
    };

    window.transferPokemon = function(index) {
        let caughtPokemon = JSON.parse(localStorage.getItem('caughtPokemon')) || [];
        const removedPokemon = caughtPokemon.splice(index, 1);
        localStorage.setItem('caughtPokemon', JSON.stringify(caughtPokemon));
        console.log(`Przeniesiono Pokémona: ${removedPokemon[0].name}`);
        displayCaughtPokemon();
        updatePokemonSelect();
    };

    function generateMap() {
        const map = [];
        for (let x = 0; x < gridCount; x++) {
            map[x] = [];
            for (let y = 0; y < gridCount; y++) {
                map[x][y] = Math.random() < 0.7 ? 0 : 1; // 70% szans na drogę, 30% na las/łąkę
            }
        }
        return map;
    }

    function drawBoard() {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        for (let x = 0; x < gridCount; x++) {
            for (let y = 0; y < gridCount; y++) {
                if (map[x][y] === 0) {
                    ctx.fillStyle = '#d3d3d3'; // Kolor drogi
                } else {
                    ctx.fillStyle = '#228B22'; // Kolor lasu/łąki
                }
                ctx.fillRect(x * gridSize, y * gridSize, gridSize, gridSize);
                ctx.strokeStyle = '#cccccc';
                ctx.strokeRect(x * gridSize, y * gridSize, gridSize, gridSize);
            }
        }
        ctx.fillStyle = 'blue';
        ctx.fillRect(player.x * gridSize, player.y * gridSize, gridSize, gridSize);
    }

    function movePlayer(dx, dy) {
        const newX = player.x + dx;
        const newY = player.y + dy;

        if (newX >= 0 && newX < gridCount && newY >= 0 && newY < gridCount) {
            player.x = newX;
            player.y = newY;
            drawBoard();
            console.log('Ruch gracza:', { x: player.x, y: player.y });
            checkForPokemonEncounter();
        }
    }

    function checkForPokemonEncounter() {
        const currentTile = map[player.x][player.y];
        let encounterChance;

        if (currentTile === 0) {
            encounterChance = 0.1; // 10% szans na spotkanie Pokémona na drodze
        } else {
            encounterChance = 0.4; // 40% szans na spotkanie Pokémona w lesie/na łące
        }

        console.log(`Aktualny teren: ${currentTile === 0 ? 'Droga' : 'Las/Łąka'}, Szansa na spotkanie: ${encounterChance * 100}%`);

        if (Math.random() < encounterChance) {
            console.log('Spotkanie z Pokémonem!');
            startBattle();
        } else {
            console.log('Brak spotkania.');
        }
    }

    function startBattle() {
        if (!wildPokemon) {
            wildPokemon = createRandomPokemon();
            if (!wildPokemon) {
                console.error('Błąd: Nie można rozpocząć bitwy, ponieważ nie ma dostępnych Pokémonów.');
                return;
            }
            modal.classList.remove('hidden');
            modalTitle.textContent = `Napotkałeś dzikiego ${wildPokemon.name}!`;
            pokemonInfo.innerHTML = `
                <img src="${wildPokemon.icon}" alt="${wildPokemon.name}" width="100">
                <p>Poziom: ${wildPokemon.level}</p>
                <p>Atak: ${wildPokemon.attack}</p>
                <p>Obrona: ${wildPokemon.defense}</p>
            `;
            battleStatus.textContent = '';
        }
    }

    attackButton.onclick = function() {
        if (wildPokemon) {
            battleStatus.textContent = 'Atakujesz...';
            modal.classList.add('attack-animation');
            setTimeout(() => {
                modal.classList.remove('attack-animation');
                if (Math.random() < 0.5) {
                    battleStatus.textContent = `Pokonałeś ${wildPokemon.name}!`;
                    catchButton.style.display = 'inline-block';
                    attackButton.style.display = 'none'; // Ukryj przycisk "Atakuj" po walce
                } else {
                    battleStatus.textContent = `${wildPokemon.name} obronił się!`;
                }
            }, 1000);
        }
    };

    catchButton.onclick = function() {
        if (wildPokemon) {
            savePokemonToStorage(wildPokemon);
            battleStatus.textContent = `Złapałeś ${wildPokemon.name}!`;
            displayCaughtPokemon();
            setTimeout(() => {
                modal.classList.add('hidden');
                catchButton.style.display = 'none';
                attackButton.style.display = 'inline-block';
                wildPokemon = null;
            }, 1000);
        }
    };

    runButton.onclick = function() {
        if (wildPokemon) {
            modal.classList.add('hidden');
            battleStatus.textContent = 'Uciekłeś od walki.';
            wildPokemon = null;
            catchButton.style.display = 'none';
            attackButton.style.display = 'inline-block';
        }
    };
	
	
	// Funkcja zapisywania listy Pokémonów do pliku JSON
    saveButton.onclick = function() {
        let caughtPokemon = JSON.parse(localStorage.getItem('caughtPokemon')) || [];
        if (caughtPokemon.length > 0) {
            const dataStr = "data:text/json;charset=utf-8," + encodeURIComponent(JSON.stringify(caughtPokemon));
            const downloadAnchor = document.createElement('a');
            downloadAnchor.setAttribute('href', dataStr);
            downloadAnchor.setAttribute('download', 'caught_pokemon.json');
            document.body.appendChild(downloadAnchor);
            downloadAnchor.click();
            downloadAnchor.remove();
        } else {
            alert('Brak zapisanych Pokémonów do zapisania.');
        }
    };

    // Funkcja wczytywania listy Pokémonów z pliku JSON
    loadInput.onchange = function(event) {
        const file = event.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                try {
                    const pokemonData = JSON.parse(e.target.result);
                    if (Array.isArray(pokemonData)) {
                        localStorage.setItem('caughtPokemon', JSON.stringify(pokemonData));
                        displayCaughtPokemon();
                        alert('Lista Pokémonów została wczytana pomyślnie.');
                    } else {
                        alert('Nieprawidłowy format pliku JSON.');
                    }
                } catch (error) {
                    console.error('Błąd podczas wczytywania pliku JSON:', error);
                    alert('Wystąpił błąd podczas wczytywania pliku JSON.');
                }
            };
            reader.readAsText(file);
        }
    };
	
	document.addEventListener('keydown', (event) => {
    // Wyświetl ukryte menu po naciśnięciu kombinacji klawiszy (np. Ctrl + M)
    if (event.ctrlKey && event.key === 'm') {
        const hiddenMenu = document.getElementById('hidden-menu');
        hiddenMenu.style.display = hiddenMenu.style.display === 'none' ? 'block' : 'none';
    }
});


    document.getElementById('up').onclick = () => movePlayer(0, -1);
    document.getElementById('down').onclick = () => movePlayer(0, 1);
    document.getElementById('left').onclick = () => movePlayer(-1, 0);
    document.getElementById('right').onclick = () => movePlayer(1, 0);

    document.addEventListener('keydown', (event) => {
        switch (event.key) {
            case 'w':
                movePlayer(0, -1);
                break;
            case 's':
                movePlayer(0, 1);
                break;
            case 'a':
                movePlayer(-1, 0);
                break;
            case 'd':
                movePlayer(1, 0);
                break;
            default:
                break;
        }
    });

    drawBoard();
    displayCaughtPokemon();
    updatePokemonSelect();
};
