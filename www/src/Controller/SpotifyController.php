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
        $new_releases = $spotify->getLanzamientos();

        return $this->render('spotify/lanzamientos.html.twig', [
            'albumns' => $new_releases->albums->items,
        ]);
    } // lanzamientos

} // SpotifyController