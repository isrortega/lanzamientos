<?php
namespace App\Controller;

use App\Service\Spotify;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * Clase para intercambiar datos con spotify y 
 */
class SpotifyController extends AbstractController {


    public function lanzamientos(Spotify $spotify) : Response {
        $new_releases = $spotify->getLanzamientos();

        return $this->render('spotify/lanzamientos.html.twig', [
            'albumns' => $new_releases->albums->items,
        ]);
    } // lanzamientos

} // SpotifyController