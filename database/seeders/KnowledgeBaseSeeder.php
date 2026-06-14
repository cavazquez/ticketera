<?php

namespace Database\Seeders;

use App\Models\KbArticle;
use App\Models\KbCategory;
use Illuminate\Database\Seeder;

class KnowledgeBaseSeeder extends Seeder
{
    public function run(): void
    {
        if (KbArticle::query()->exists()) {
            return;
        }

        $gettingStarted = KbCategory::create([
            'name' => 'Primeros pasos',
            'slug' => 'primeros-pasos',
            'description' => 'Guías para empezar a usar el sistema.',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $tickets = KbCategory::create([
            'name' => 'Tickets',
            'slug' => 'tickets',
            'description' => 'Creación y seguimiento de consultas.',
            'sort_order' => 2,
            'is_active' => true,
        ]);

        KbArticle::create([
            'kb_category_id' => $gettingStarted->id,
            'title' => '¿Cómo creo un ticket?',
            'slug' => 'como-creo-un-ticket',
            'summary' => 'Pasos para abrir una consulta de soporte.',
            'body' => "1. Iniciá sesión con tu cuenta de cliente.\n2. Andá a «Nuevo ticket».\n3. Elegí el departamento y la prioridad.\n4. Describí tu problema con el mayor detalle posible.\n5. Enviá el formulario.\n\nRecibirás notificaciones por correo cuando el equipo responda.",
            'is_published' => true,
            'is_featured' => true,
            'sort_order' => 1,
            'published_at' => now(),
        ]);

        KbArticle::create([
            'kb_category_id' => $gettingStarted->id,
            'title' => '¿Cómo sigo el estado de mi consulta?',
            'slug' => 'como-sigo-el-estado',
            'summary' => 'Consultá el historial y las respuestas de tu ticket.',
            'body' => "Desde «Mis tickets» podés ver todos tus casos abiertos y cerrados.\n\nAl entrar a un ticket verás:\n- El estado actual (abierto, en progreso, resuelto, cerrado)\n- Las respuestas del equipo de soporte\n- Archivos adjuntos si los hubiera\n\nTambién podés responder directamente desde la misma pantalla.",
            'is_published' => true,
            'is_featured' => true,
            'sort_order' => 2,
            'published_at' => now(),
        ]);

        KbArticle::create([
            'kb_category_id' => $tickets->id,
            'title' => '¿Qué prioridad debo elegir?',
            'slug' => 'que-prioridad-elegir',
            'summary' => 'Cuándo usar baja, normal, alta o urgente.',
            'body' => "Baja: consultas generales sin impacto inmediato.\nNormal: la mayoría de los casos.\nAlta: el problema afecta tu trabajo pero hay alternativa.\nUrgente: el servicio está caído o hay impacto crítico en producción.\n\nEl equipo puede ajustar la prioridad si es necesario.",
            'is_published' => true,
            'is_featured' => false,
            'sort_order' => 1,
            'published_at' => now(),
        ]);

        KbArticle::create([
            'kb_category_id' => $tickets->id,
            'title' => '¿Puedo adjuntar archivos?',
            'slug' => 'adjuntar-archivos',
            'summary' => 'Cómo enviar capturas, logs o documentos.',
            'body' => "Sí. Al crear un ticket o responder podés adjuntar archivos (capturas, logs, PDFs).\n\nRecomendaciones:\n- No incluyas contraseñas en capturas\n- Comprimí logs muy grandes si es posible\n- Mencioná en el texto qué muestra cada adjunto",
            'is_published' => true,
            'is_featured' => false,
            'sort_order' => 2,
            'published_at' => now(),
        ]);
    }
}
