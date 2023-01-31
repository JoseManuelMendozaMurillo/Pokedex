# Pokedex
Simple pokedex

Descripción:

Este proyecto contiene una interfaz para buscar el nombre de un pokemón y obtener algunos de sus datos relevantes así como tambien el rap del pokemon.
Se conecta a una fachada hecha en PHP, la cual a su vez se conecta a APIS como la Poke API, de donde se obtiene los datos mas relevantes del pokemon y 
tambien se conecta a la API de Youtube para hacer la busqueda del rap del pokemón y descargarlo (descarga el primer audio del video que arroje la
API como resultado del parametro de busqueda). Sí el rap ya esta descargado, no hace la busqueda y simplemente regresa toda la información al cliente
en formato JSON.

Dependencias: 

  1. youtube-dl
  2. FFmpeg
