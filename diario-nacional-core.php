<?php
/**
 * Plugin Name: Diario Nacional Core
 * Plugin URI: https://github.com/haye-letayf/diario-nacional-core
 * Description: Motor de publicación de edictos, créditos, cobros (Stripe) y facturación (Digifact) de Diario Nacional. Toda la lógica de negocio vive aquí; la presentación vive en el tema diario-nacional-theme.
 * Version: 0.1.0
 * Author: Once24
 * Text Domain: diario-nacional-core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Acceso directo no permitido.
}

define( 'DIARIO_NACIONAL_CORE_VERSION', '0.1.0' );
define( 'DIARIO_NACIONAL_CORE_PATH', plugin_dir_path( __FILE__ ) );
define( 'DIARIO_NACIONAL_CORE_URL', plugin_dir_url( __FILE__ ) );

/*
 * Los módulos se van agregando aquí conforme avanzan las fases del plan
 * (ver CLAUDE.md). Cada uno vive en includes/, un archivo por concern,
 * mismo patrón que openreal-cloud:
 *
 *   Fase 1 — includes/class-auth.php        Cuentas, sesiones, roles, reset de contraseña
 *   Fase 2 — includes/class-edictos.php      Motor de edictos (publicación, 5 fechas, buscador)
 *   Fase 3 — includes/class-stripe.php       Checkout, webhook, créditos
 *   Fase 4 — includes/class-facturacion.php  Integración Digifact/Sicofi (contrato portado 1:1)
 *   Fase 5 — includes/class-evidencia.php    PDF de evidencia + notificaciones por correo (AWS SES)
 *   Fase 6 — includes/class-noticias.php     CPT + ACF para el extracto de noticias
 *
 * Nada de esto existe todavía — este commit es solo el esqueleto de Fase 0.
 */
