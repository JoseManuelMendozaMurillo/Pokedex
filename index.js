/* COSAS QUE FALTAN
 * Falta hacer el diseño responsivo
 */
const pokemonName = document.getElementById("namePokemon");
const btnSearchPokemon = document.getElementById("btnSearchPokemon");
const btnCloseCard = document.getElementById("btnCloseCard");
const pokemonImg = document.getElementById("pokemonImg");
const card = document.getElementById("pokemonCard");
const animationLoading = document.getElementById("animationLoading");
let animation;

card.style.display = "none";
animationLoading.style.display = "none";

btnSearchPokemon.addEventListener("click", async () => {
  const value = pokemonName.value.toLowerCase();

  // Validamos que se haya escrito el nombre de un pokemón
  if (value.trim().length === 0) {
    alert("Ingrese el nombre de un Pokemón");
    return;
  }

  // Ocultamos la card anterior y mostramos la animación de carga
  card.style.display="none";
  animationLoading.style.display = "block";

  // Obtenemos la información del pokemón
  const pokemonInfo = await SearchPokemonInfo(value);

  // Ocultamos la animación de carga para mostrar la nueva card
  animationLoading.style.display = "none";

  console.log(pokemonInfo);
  // Creamos la nueva card y la mostramos al usuario
  printCard(pokemonInfo);
});

btnCloseCard.addEventListener("click", () => {
  setTimeout(() => {
    card.style.display = "none";
    clearInterval(animation);
  }, 400);
});

/**
 * printCard
 *
 * @param Object pokemonInfo
 * @return Void
 */
function printCard(pokemonInfo) {
  // Función que obteniene la información del pokemon y la pinta en la card

  // Eliminamos la animación anterior e iniciamos una con las nuevas imagenes
  clearInterval(animation);
  AnimatePokemonImg(pokemonInfo.images.front, pokemonInfo.images.back);

  const name = document.getElementById("name");
  const experience = document.getElementById("exp");
  const attack = document.getElementById("attack");
  const specialAttack = document.getElementById("specialAttack");
  const defense = document.getElementById("defense");
  const nameRap = document.getElementById("nameRap");
  const pokemonRap = document.getElementById("pokemonRap");

  name.innerHTML = `${pokemonInfo.name}<span class="card-body-name-life-pokemon" id="life"> ${pokemonInfo.life}hp</span>`;
  experience.textContent = pokemonInfo.experience + " exp";
  nameRap.textContent = pokemonInfo.name.toLowerCase();
  pokemonRap.src = "data:audio/mp3;base64," + pokemonInfo.pokemonRap;
  attack.textContent = pokemonInfo.attack + "K";
  specialAttack.textContent = pokemonInfo.specialAttack + "K";
  defense.textContent = pokemonInfo.defense + "K";
  card.style.display = "block";
}

/**
 * AnimatePokemonImg
 *
 * @param String pokemonImgFront, PokemonImgBack
 * @returns Void
 */
function AnimatePokemonImg(pokemonImgFront, pokemonImgBack) {
  let angle = 0;
  animation = setInterval(() => {
    angle === 360 ? (angle = 0) : (angle = angle + 60);

    if (angle === 120) {
      pokemonImg.src = pokemonImgBack;
    } else if (angle === 360) {
      pokemonImg.src = pokemonImgFront;
    }

    pokemonImg.style.transform = `rotate3d(0, 1, 0, ${angle}deg)`;
  }, 185);
}

/**
 * SearchPokemon
 *
 * @param String pokemonName
 * @return Object
 */
async function SearchPokemonInfo(pokemonName) {
  // Función para buscar el pokemón y obtener su información
  try {
    const ruta = "./server/PokeApi.php";
    const request = await fetch(ruta, {
      method: "POST",
      body: JSON.stringify({ pokemonName: pokemonName }),
    });
    const response = await request.json();
    return response;
  } catch (err) {
    console.error(
      "Ucurrio un error al intentar buscar la información del pokemón.\n",
      err
    );
  }
}
