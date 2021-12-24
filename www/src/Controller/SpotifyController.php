<?php
namespace App\Controller;

use App\Service\Spotify;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * Clase para intercambiar entre el servicio de spotify y las vista
 */
class SpotifyController extends AbstractController {


    /**
     * Con este método solicitamos al servicio de Spotify que nos suministre
     * los lanzamientos recientes para renderizarlos en una plantilla twig
     *
     * @param Spotify $spotify Servicio de Spotify
     * @return Response 
     */
    public function lanzamientos(Spotify $spotify) : Response {
        $new_releases = $spotify->getNewReleases();

        return $this->render('spotify/lanzamientos.html.twig', [
            'albumns' => $new_releases->albums->items,
        ]);
    } // lanzamientos


    /**
     * Con este método solicitamos al servicio de Spotify que nos suministre
     * los datos del artista y el listado de sus top_tracks
     *
     * @param Spotify $spotify  Servicio de Spotify
     * @param String|null $artist_id El id del artista
     * @return Response
     */
    public function artista(Spotify $spotify, String $artist_id = null) : Response {
        $top_tracks = null;
        $artist_error = null;
        
        if(is_null($artist_id)){
            // todo retornar a página de artista no válido
        }
        $artist = $spotify->getArtistById($artist_id);
        if(isset($artist->error)){
            $artist_error = "Error al consultar el artista '" . $artist->error->message . "'";
            $artist = null;
        } else {
            $top_tracks = $spotify->getTopTracksByArtistId($artist_id);
        }

        return $this->render('spotify/artista.html.twig', [
            'artist' => $artist,
            'top_tracks' => ((isset($top_tracks->tracks)) ? $top_tracks->tracks : null),
            'artist_error' => $artist_error,
        ]);
    } // artista

} // SpotifyController