<?php

namespace Database\Seeders;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Enums\UserRole;
use App\Models\Department;
use App\Models\Ticket;
use App\Models\TicketReply;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $soporte = Department::firstOrCreate(
            ['name' => 'Soporte'],
            ['description' => 'Consultas técnicas y problemas con el servicio.']
        );

        $ventas = Department::firstOrCreate(
            ['name' => 'Ventas'],
            ['description' => 'Consultas comerciales y cotizaciones.']
        );

        $facturacion = Department::firstOrCreate(
            ['name' => 'Facturación'],
            ['description' => 'Pagos, facturas y temas contables.']
        );

        User::updateOrCreate(
            ['email' => 'admin@ticketera.test'],
            [
                'name' => 'Admin Sistema',
                'password' => Hash::make('password'),
                'role' => UserRole::Admin,
                'department_id' => $soporte->id,
                'email_verified_at' => now(),
            ]
        );

        $agente1 = User::updateOrCreate(
            ['email' => 'maria@ticketera.test'],
            [
                'name' => 'María Agente',
                'password' => Hash::make('password'),
                'role' => UserRole::Agent,
                'department_id' => $soporte->id,
                'email_verified_at' => now(),
            ]
        );

        $agente2 = User::updateOrCreate(
            ['email' => 'carlos@ticketera.test'],
            [
                'name' => 'Carlos Agente',
                'password' => Hash::make('password'),
                'role' => UserRole::Agent,
                'department_id' => $ventas->id,
                'email_verified_at' => now(),
            ]
        );

        $cliente1 = User::updateOrCreate(
            ['email' => 'cliente@ticketera.test'],
            [
                'name' => 'Juan Cliente',
                'password' => Hash::make('password'),
                'role' => UserRole::Client,
                'email_verified_at' => now(),
            ]
        );

        $cliente2 = User::updateOrCreate(
            ['email' => 'ana@ticketera.test'],
            [
                'name' => 'Ana Cliente',
                'password' => Hash::make('password'),
                'role' => UserRole::Client,
                'email_verified_at' => now(),
            ]
        );

        if (Ticket::query()->exists()) {
            $this->call(KnowledgeBaseSeeder::class);

            return;
        }

        $ticket1 = Ticket::create([
            'subject' => 'No puedo acceder a mi cuenta',
            'body' => 'Desde ayer no puedo iniciar sesión. Me aparece un error de credenciales inválidas.',
            'status' => TicketStatus::Open,
            'priority' => TicketPriority::High,
            'user_id' => $cliente1->id,
            'department_id' => $soporte->id,
            'assigned_to' => $agente1->id,
        ]);

        TicketReply::create([
            'ticket_id' => $ticket1->id,
            'user_id' => $agente1->id,
            'body' => 'Hola Juan, ¿podés confirmar si probaste restablecer la contraseña?',
            'is_internal' => false,
        ]);

        TicketReply::create([
            'ticket_id' => $ticket1->id,
            'user_id' => $agente1->id,
            'body' => 'Revisar logs de autenticación del usuario.',
            'is_internal' => true,
        ]);

        Ticket::create([
            'subject' => 'Consulta sobre plan premium',
            'body' => 'Quisiera saber qué incluye el plan premium y si hay descuento anual.',
            'status' => TicketStatus::InProgress,
            'priority' => TicketPriority::Normal,
            'user_id' => $cliente2->id,
            'department_id' => $ventas->id,
            'assigned_to' => $agente2->id,
        ]);

        Ticket::create([
            'subject' => 'Factura duplicada',
            'body' => 'Recibí dos facturas por el mismo mes. Necesito que revisen el cobro.',
            'status' => TicketStatus::Open,
            'priority' => TicketPriority::Urgent,
            'user_id' => $cliente1->id,
            'department_id' => $facturacion->id,
        ]);

        $this->call(KnowledgeBaseSeeder::class);
    }
}
