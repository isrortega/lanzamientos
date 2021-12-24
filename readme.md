# Lanzamientos en Spotify

---
## Requerimientos
Se requiere diseñar un módulo que cumpla con las siguientes carácteristicas:
- Consume de la **API de Spotify** para obtener información de:
    - Artistas
    - Albumes


### Lanzamientos
Crear la página /lanzamientos que muestre en un listado los últimos 
lanzamientos que se encuentran en Spotify. El listado debe tener Imagen del 
lanzamiento, nombre del lanzamiento y los artistas relacionados (cada artista
 debe ser un link).


### Artistas
Crear la página /artista/[id], donde id corresponde al identificador del 
artista, esta página se llama desde la página anterior haciendo clic en el 
artista. Se debe mostrar la información del artista como foto y nombre seguido
de una tabla donde sus cabeceras corresponden a: Foto del álbum, nombre del 
álbum y canción.


---
## Uso
### Variables de entorno
Debe definir sus variables de entorno en el archivo `.env.local`
```
SP_CLIENT_ID='<INGRESAR SPOTIFY_CLIENT_ID>'
SP_CLIENT_SECRET='<INGRESAR SPOTIFY_CLIENT_SECRET>'
```


### docker-compose
El proyecto cuenta con un archivo `docker-compose` así que si tiene este 
software instalado puede poner a correr el servidor con el comando:
```
docker-compose up -d
```
Al ejecutar el servidor de esta forma, el servicio quedará expuesto en el puerto 8000

Y detener la ejecución del mismo con el comando 
```
docker-compose down
```

Así como también puede ingresar a la consola del servidor con el comando
```
docker-compose exec myapp bash
```
Si no cuenta con `docker-compose` el proyecto de igual manera se puede ejecutar
como cualquier otro proyecto de symfony.

### Actualizar composer
Debe actualizar las librerías usadas con composer con el fin de poder ejecutar la 
aplicación, lo logra con el comando `composer update`

Si tiene `docker-compose` en su sistema puede actualizar composer ejecutando el
siguiente comando:
```
docker-compose exec myapp composer update
```

