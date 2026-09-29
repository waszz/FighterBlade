<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PoderesSeeder extends Seeder
{
    public function run()
    {
        $poderes = [
            [
                'nombre'        => 'ABSORVER SALUD', //!!HECHO!!
                'descripcion'   => 'Se regenera un 75% del daño ejercido al enemigo.',
                'modificadores' => json_encode([
                    [
                        'tipo'      => 'porcentaje_regeneracion',
                        'valor'     => 75,
                        'stat_base' => 'danio_ejercido',
                    ],
                ]),
                'imagen'        => 'absorver_salud.png',
            ],
            [
                'nombre'        => 'ATAQUE DESESPERADO', //!!HECHO!!
                'descripcion'   => '100% de chances de atacar un Round extra cuando vas Perdiendo',
                'modificadores' => json_encode([
                    [
                        'tipo'      => 'ataque_extra',
                        'condicion' => 'perdiendo',
                        'chance'    => 100,
                    ],
                ]),
                'imagen'        => 'ataque_desesperado.png',
            ],
            [
                'nombre'        => 'ATAQUE TRAICIONERO', //!!HECHO!!
                'descripcion'   => '100% de chances de atacar un Round extra cuando es un Empate',
                'modificadores' => json_encode([
                    [
                        'tipo'      => 'ataque_extra',
                        'condicion' => 'empate',
                        'chance'    => 100,
                    ],
                ]),
                'imagen'        => 'ataque_traicionero.png',
            ],
            [
                'nombre'        => 'ATURDIR',//!!HECHO!!
                'descripcion'   => '10% de chances de dejar Aturdido al contrincante en pelea',
                'modificadores' => json_encode([
                    [
                        'tipo'    => 'estado',
                        'estado'  => 'Aturdido',
                        'chance'  => 10,
                    ],
                ]),
                'imagen'        => 'aturdir.png',
            ],
            [
                'nombre'        => 'CAMUFLAJE',
                'descripcion'   => 'Evita que el luchador sea atacado',
                'modificadores' => json_encode([
                    [
                        'tipo'  => 'evitar_ataque',
                        'valor' => true,
                    ],
                ]),
                'imagen'        => 'camuflaje.png',
            ],
            [
                'nombre'        => 'COMBO VELOZ', //!!HECHO!!
                'descripcion'   => 'Aumenta en un 100% VEL durante los primeros 3 rounds',
                'modificadores' => json_encode([
                    [
                        'tipo'             => 'aumento_stat',
                        'stat'             => 'velocidad',
                        'valor_porcentaje' => 100,
                        'duracion'         => 3,
                    ],
                ]),
                'imagen'        => 'combo_veloz.png',
            ],
            [
                'nombre'        => 'CONGELAR',//!!HECHO!!
                'descripcion'   => 'Ejerce daño directo equivalente al 25% de una tirada de ENE. 10% de chances de dejar Congelado al contrincante en pelea',
                'modificadores' => json_encode([
                    [
                        'tipo'       => 'daño_directo',
                        'porcentaje' => 25,
                        'stat_base'  => 'energia',
                    ],
                    [
                        'tipo'   => 'estado',
                        'estado' => 'Congelado',
                        'chance' => 10,
                    ],
                ]),
                'imagen'        => 'congelar.png',
            ],
            [
                'nombre'        => 'CONTROL CLIMATICO', //!!HECHO!!
                'descripcion'   => 'Reduce en un 50% VEL del enemigo',
                'modificadores' => json_encode([
                    [
                        'tipo'     => 'modificador_stat',
                        'stat'     => 'velocidad',
                        'valor'    => -50,
                        'duracion' => null,
                    ],
                ]),
                'imagen'        => 'control_climatico.png',
            ],
            [
                'nombre'        => 'DAMAGE ABSORV', //!!HECHO!!
                'descripcion'   => 'Reduce el 100% del daño Directo recibido en pelea',
                'modificadores' => json_encode([
                    [
                        'tipo'       => 'reduccion_danio',
                        'porcentaje' => 100,
                        'tipo_danio' => 'daño_directo',
                        
                    ],
                ]),
                'imagen'        => 'damage_absorv.png',
            ],
            [
                'nombre'        => 'DIRECT DAMAGE',//!!HECHO!!
                'descripcion'   => 'Ejerce daño directo equivalente al 75% de una tirada de ENE',
                'modificadores' => json_encode([
                    [
                        'tipo'       => 'daño_directo',
                        'porcentaje' => 75,
                        'stat_base'  => 'energia',
                    ],
                ]),
                'imagen'        => 'direct_damage.png',
            ],
            [
                'nombre'        => 'ENEMISTAD', //!!HECHO!!
                'descripcion'   => 'Aumenta en un 25% FUE, ATA, VEL durante los primeros 5 rounds',
                'modificadores' => json_encode([
                    [
                        'tipo'            => 'aumento_stat',
                        'porcentaje'      => 25,
                        'stats_afectados' => ['fuerza', 'ataque', 'velocidad'],
                        'duracion'        => 5,
                    ],
                ]),
                'imagen'        => 'enemistad.png',
            ],
            [
                'nombre'        => 'ENERGIZADO', //!!HECHO!!
                'descripcion'   => 'Optimiza VEL y ENE sumándole al menor el valor del mayor multiplicado por 0.8',
                'modificadores' => json_encode([
                    [
                        'tipo'   => 'optimizar_stats',
                        'stats'  => ['velocidad', 'energia'],
                        'factor' => 0.8,
                    ],
                ]),
                'imagen'        => 'energizado.png',
            ],
            [
                'nombre'        => 'ENVENENAR',//!!HECHO!!
                'descripcion'   => '10% de chances de dejar Envenenado al contrincante en pelea',
                'modificadores' => json_encode([
                    [
                        'tipo' => 'estado',
                        'estado' => 'Envenenado',
                        'chance' => 10,
                    ],
                ]),
                'imagen'        => 'envenenar.png',
            ],
            [
                'nombre'        => 'ESPINAS', //!!HECHO!!
                'descripcion'   => 'Devuelve un 45% del daño recibido. 10% de chances de dejar Envenenado al contrincante en pelea',
                'modificadores' => json_encode([
                    [
                        'tipo'       => 'daño_directo',
                        'porcentaje' => 45,
                    ],
                    [
                        'tipo' => 'estado',
                        'estado' => 'Envenenado',
                        'chance' => 10,
                    
                    ],
                ]),
                'imagen'        => 'espinas.png',
            ],
            [
                'nombre'        => 'FRENESÍ', //!!HECHO!!
                'descripcion'   => '20% de chance de entrar en Frenesí. Un estado en el que se mejora en un 35% ATA, FUE y VEL',
                'modificadores' => json_encode([
                    [
                        'tipo' => 'estado_en_ronda',
                        'estado' => 'Frenesí',
                        'chance' => 20,
                        'modificaciones_stat' => [
                            [
                                'porcentaje' => 35,
                                'stats'      => ['ataque', 'fuerza', 'velocidad'],
                            ],
                        ],
                    ],
                ]),
                'imagen'        => 'frenesi.png',
            ],
            [
                'nombre'        => 'FURIA CIEGA', //!!HECHO!!
                'descripcion'   => '20% de chance de entrar en Furia Ciega. Un estado en el que se mejora en un 25% ATA, FUE y ENE y se reduce en un 25% DEF y RES',
                'modificadores' => json_encode([
                    [
                        'tipo'                => 'estado_en_ronda',
                        'nombre_estado'       => 'Furia Ciega',
                        'chance'              => 20,
                        'modificaciones_stat' => [
                            [
                                'porcentaje' => 25,
                                'stats'      => ['ataque', 'fuerza', 'energia'],
                            ],
                            [
                                'porcentaje' => -25,
                                'stats'      => ['defensa', 'resistencia'],
                            ],
                        ],
                    ],
                ]),
                'imagen'        => 'furia_ciega.png',
            ],
            [
                'nombre'        => 'GOLPES VELOCES', //!!HECHO!!
                'descripcion'   => 'Optimiza ATA y VEL sumándole al menor el valor del mayor multiplicado por 0.8',
                'modificadores' => json_encode([
                    [
                        'tipo'   => 'optimizar_stats',
                        'stats'  => ['ataque', 'velocidad'],
                        'factor' => 0.8,
                    ],
                ]),
                'imagen'        => 'golpes_veloces.png',
            ],
            [
                'nombre'        => 'HEMORRAGIA',//!!HECHO!!
                'descripcion'   => 'Ejerce daño directo equivalente al 35% de una tirada de ATA. 10% de chances de dejar Desangrado al contrincante en pelea',
                'modificadores' => json_encode([
                    [
                        'tipo'       => 'daño_directo',
                        'porcentaje' => 35,
                        'stat_base'  => 'ataque',
                    ],
                    [
                        'tipo' => 'estado',
                        'estado' => 'Desangrado',
                        'chance' => 10,
                        
                    ],
                ]),
                'imagen'        => 'hemorragia.png',
            ],
            [
                'nombre'        => 'INSTINTO MEJORADO', //!!HECHO!!
                'descripcion'   => 'Aumenta en un 100% VEL, ATA y ENE durante el primer round. Anula los siguientes poderes: Camuflaje',
                'modificadores' => json_encode([
                    [
                        'tipo'       => 'buff_temporal',
                        'porcentaje' => 100,
                        'stats'      => ['velocidad', 'ataque', 'energia'],
                        'duracion'   => 1,
                    ],
                    [
                        'tipo'    => 'anulacion_poder',
                        'poderes' => ['Camuflaje'],
                    ],
                ]),
                'imagen'        => 'instinto_mejorado.png',
            ],
            [
                'nombre'        => 'INTIMIDAR', //!!HECHO!!
                'descripcion'   => 'Reduce en un 75% DEF del enemigo',
                'modificadores' => json_encode([
                    [
                        'tipo'       => 'debuff',
                        'porcentaje' => -75,
                        'stats'      => ['defensa'],
                    ],
                ]),
                'imagen'        => 'intimidar.png',
            ],
            [
                'nombre'        => 'MÁXIMA POTENCIA', //!!HECHO!!
                'descripcion'   => 'Aumenta el daño de tus Especiales en un 50%',
                'modificadores' => json_encode([
                    [
                        'tipo'       => 'buff_especiales',
                        'porcentaje' => 50,
                        'stats'      => ['velocidad'], // o el stat relacionado para daño especial
                    ],
                ]),
                'imagen'        => 'maxima_potencia.png',
            ],
            // [
            //     'nombre'        => 'METAMORFOSIS',
            //     'descripcion'   => '50% de chance de transformarse en un set de el mismo nivel que el set actual',
            //     'modificadores' => json_encode([
            //         [
            //             'tipo'                => 'transformacion',
            //             'chance'              => 100,
            //             'tipo_transformacion' => 'igual_nivel',
            //         ],
            //     ]),
            //     'imagen'        => 'metamorfosis.png',
            // ],
            // [
            //     'nombre'        => 'METAMORFOSIS SUPERIOR',
            //     'descripcion'   => '99% de chance de transformarse en un set de nivel 100',
            //     'modificadores' => json_encode([
            //         [
            //             'tipo'                => 'transformacion',
            //             'chance'              => 99,
            //             'tipo_transformacion' => 'nivel_100',
            //         ],
            //     ]),
            //     'imagen'        => 'metamorfosis_superior.png',
            // ],
            [
                'nombre'        => 'MOLE', //!!HECHO!!
                'descripcion'   => 'Optimiza DEF y RES sumándole al menor el valor del mayor multiplicado por 0.8',
                'modificadores' => json_encode([
                    [
                        'tipo'   => 'optimizar_stats',
                        'stats'  => ['defensa', 'resistencia'],
                        'factor' => 0.8,
                    ],
                ]),
                'imagen'        => 'mole.png',
            ],
            [
                'nombre'        => 'OFENSIVO EXPERTO', //!!HECHO!!
                'descripcion'   => 'Optimiza FUE y ATA sumándole al menor el valor del mayor multiplicado por 0.8',
                'modificadores' => json_encode([
                    [
                        'tipo'   => 'optimizar_stats',
                        'stats'  => ['fuerza', 'ataque'],
                        'factor' => 0.8,
                    ],
                ]),
                'imagen'        => 'ofensivo_experto.png',
            ],
            [
                'nombre'        => 'PARALIZAR',//!!HECHO!!
                'descripcion'   => '10% de chances de dejar Paralizado al contrincante en pelea',
                'modificadores' => json_encode([
                    [
                        'tipo' => 'estado',
                        'estado' => 'Paralizado',
                        'chance' => 10,
                        
                    ],
                ]),
                'imagen'        => 'paralizar.png',
            ],
            [
                'nombre'        => 'PIEL DURA', //!!HECHO!!
                'descripcion'   => 'Reduce el 25% del daño Físico recibido en pelea',
                'modificadores' => json_encode([
                    [
                        'tipo'       => 'reduccion_danio',
                        'porcentaje' => 25,
                        'tipo_danio' => 'fisico',
                        'duracion'   => 5,
                    ],
                ]),
                'imagen'        => 'piel_dura.png',
            ],
            [
                'nombre'        => 'PIEL IMPENETRABLE', //!!HECHO!!
                'descripcion'   => 'Reduce el 50% del daño Físico recibido en pelea',
                'modificadores' => json_encode([
                    [
                        'tipo'       => 'reduccion_danio',
                        'porcentaje' => 50,
                        'tipo_danio' => 'fisico',
                        'duracion'   => 5,
                    ],
                ]),
                'imagen'        => 'piel_impenetrable.png',
            ],
            [
                'nombre'        => 'QUEMAR',//!!HECHO!!
                'descripcion'   => 'Ejerce daño directo equivalente al 25% de una tirada de ATA. 10% de chances de dejar Quemado al contrincante en pelea',
                'modificadores' => json_encode([
                    [
                        'tipo'       => 'daño_directo',
                        'porcentaje' => 25,
                        'stat_base'  => 'ataque',
                    ],
                    [
                        'tipo' => 'estado',
                        'estado' => 'Quemado',
                        'chance' => 10,
                        
                    ],
                ]),
                'imagen'        => 'quemar.png',
            ],
            [
                'nombre'        => 'REGENERAR SUPERIOR', //!!HECHO!!
                'descripcion'   => 'Se regenera 50% del daño recibido',
                'modificadores' => json_encode([
                    [
                        'tipo'       => 'regeneracion',
                        'porcentaje' => 50,
                        'base'       => 'danio_recibido', // <-- aquí el cambio clave
                    ],
                ]),
                'imagen'        => 'reflejar.png',
            ],
            [
                'nombre'        => 'REGENERAR', //!!HECHO!!
                'descripcion'   => 'Se regenera 20% del daño recibido',
                'modificadores' => json_encode([
                    [
                        'tipo'       => 'regeneracion',
                        'porcentaje' => 20,
                        'base'       => 'danio_recibido',
                    ],
                ]),
                'imagen' => 'regenerar.png',
            ],
            [
                'nombre'        => 'SIEMPRE EN PIE',//!!HECHO!!
                'descripcion'   => 'Reduce en un 100% el Tiempo de Recuperación.',
                'modificadores' => json_encode([
                    [
                        'tipo'       => 'reduccion_tiempo_recuperacion',
                        'porcentaje' => 100,
                        'duracion'   => null,
                    ],
                ]),
                'imagen' => 'siempre_en_pie.png',
            ],
            [
                'nombre'  => 'SUERTUDO',//!!HECHO!!
                'descripcion'   => 'Aumenta en un 100% la cantidad de Oro dropeada por los enemigos y otorga un 10% de probabilidad de obtener ítems.',
                'modificadores' => json_encode([
                    [
                        'tipo'   => 'multiplicador_drop_oro',
                        'factor' => 2,
                    ],
                    [
                        'tipo'       => 'chance_drop_item',
                        'porcentaje' => 10,
                    ],
                ]),
                'imagen' => 'suertudo.png',
            ],
            [
                'nombre'        => 'ROBAR VIDA',//!!HECHO!!
                'descripcion'   => 'Se regenera un 35% del daño ejercido al enemigo',
                'modificadores' => json_encode([
                    [
                        'tipo'      => 'porcentaje_regeneracion',
                        'valor'     => 35,
                        'stat_base' => 'danio_ejercido',
                    ],
                ]),
                'imagen' => 'robar_vida.png',
            ],
            [
                'nombre'        => 'SANGRADO',//!!HECHO!!
                'descripcion'   => 'Ejerce daño directo equivalente al 25% de una tirada de ATA. 10% de chances de dejar Desangrado al contrincante en pelea',
                'modificadores' => json_encode([
                    [
                        'tipo'       => 'daño_directo',
                        'porcentaje' => 25,
                        'stat_base'  => 'ataque',
                    ],
                    [
                        'tipo' => 'estado',
                        'estado' => 'Desangrado',
                        'chance' => 10,
                        
                    ],
                ]),
                'imagen'        => 'sangrado.png',
            ],
            [
                'nombre'        => 'SUPER ATAQUE',//!!HECHO!!
                'descripcion'   => 'Multiplica el valor de ATA por 1.75.',
                'modificadores' => json_encode([
                    [
                        'tipo'   => 'multiplicador_stat',
                        'stat'   => 'ataque',
                        'factor' => 1.75,
                    ],
                ]),
                'imagen'        => 'super_ataque.png',
            ],
            [
                'nombre'        => 'SUPER CARGA',//!!HECHO!!
                'descripcion'   => '30% de chance de entrar en Super Carga. Mejora en un 25% ENE y VEL.',
                'modificadores' => json_encode([
                    [
                        'tipo'           => 'estado_con_modificacion_stat',
                        'nombre_estado'  => 'Super Carga',
                        'chance'         => 30,
                        'modificaciones' => [
                            [
                                'stat'   => 'energia',
                                'factor' => 1.25,
                            ],
                            [
                                'stat'   => 'velocidad',
                                'factor' => 1.25,
                            ],
                        ],
                        'duracion'       => 5,
                    ],
                ]),
                'imagen'        => 'super_carga.png',
            ],
            [
                'nombre'        => 'SUPER DEFENSA',//!!HECHO!!
                'descripcion'   => 'Multiplica el valor de DEF por 1.75.',
                'modificadores' => json_encode([
                    [
                        'tipo'   => 'multiplicador_stat',
                        'stat'   => 'defensa',
                        'factor' => 1.75,
                    ],
                ]),
                'imagen'        => 'super_defensa.png',
            ],
            [
                'nombre'        => 'SUPER ENERGÍA',//!!HECHO!!
                'descripcion'   => 'Multiplica el valor de ENE por 1.75.',
                'modificadores' => json_encode([
                    [
                        'tipo'   => 'multiplicador_stat',
                        'stat'   => 'energia',
                        'factor' => 1.75,
                    ],
                ]),
                'imagen'        => 'super_energia.png',
            ],
            [
                'nombre'        => 'SUPER FUERZA',//!!HECHO!!
                'descripcion'   => 'Multiplica el valor de FUE por 1.75.',
                'modificadores' => json_encode([
                    [
                        'tipo'   => 'multiplicador_stat',
                        'stat'   => 'fuerza',
                        'factor' => 1.75,
                    ],
                ]),
                'imagen'        => 'super_fuerza.png',
            ],
            [
                'nombre'        => 'SUPER NOVA',//!!HECHO!!
                'descripcion'   => '20% de chance de entrar en Super Nova. Un estado en el que se mejora en un 50% ENE y VEL.',
                'modificadores' => json_encode([
                    [
                        'tipo'           => 'estado_con_modificacion_stat',
                        'nombre_estado'  => 'Super Nova',
                        'chance'         => 20,
                        'modificaciones' => [
                            ['stat' => 'energia', 'factor' => 1.5],
                            ['stat' => 'velocidad', 'factor' => 1.5],
                        ],
                        'duracion'       => 5,
                    ],
                ]),
                'imagen'        => 'super_nova.png',
            ],
            [
                'nombre'        => 'SUPER RESISTENCIA',//!!HECHO!!
                'descripcion'   => 'Multiplica el valor de RES por 1.75.',
                'modificadores' => json_encode([
                    [
                        'tipo'   => 'multiplicador_stat',
                        'stat'   => 'resistencia',
                        'factor' => 1.75,
                    ],
                ]),
                'imagen'        => 'super_resistencia.png',
            ],
            [
                'nombre'        => 'SUPER SENTIDOS',//!!HECHO!!
                'descripcion'   => 'Aumenta en un 50% durante los primeros 3 rounds ATA, FUE y VEL. Anula los siguientes poderes: Camuflaje.',
                'modificadores' => json_encode([
                    [
                        'tipo'       => 'buff_temporal',
                        'porcentaje' => 50,
                        'stats'      => ['ataque', 'fuerza', 'velocidad'],
                        'duracion'   => 3,
                    ],
                    [
                        'tipo'    => 'anulacion_poder',
                        'poderes' => ['Camuflaje'],
                    ],
                ]),
                'imagen'        => 'super_sentidos.png',
            ],
            [
                'nombre'        => 'SUPER VELOCIDAD',//!!HECHO!!
                'descripcion'   => 'Reduce en un 50% el tiempo de viaje y multiplica el valor de VEL por 1.75.',
                'modificadores' => json_encode([
                    [
                        'tipo'   => 'multiplicador_stat',
                        'stat'   => 'velocidad',
                        'factor' => 1.75,
                    ],
                    [
                        'tipo'       => 'reduccion_tiempo_viaje',
                        'porcentaje' => 50,
                        'duracion'   => null,
                    ],
                ]),
                'imagen'        => 'super_velocidad.png',
            ],
            [
                'nombre'        => 'TÉCNICAS CERTERAS',//!!HECHO!!
                'descripcion'   => 'Optimiza FUE y VEL sumándole al menor el valor del mayor multiplicado por 0.8.',
                'modificadores' => json_encode([
                    [
                        'tipo'   => 'optimizar_stats',
                        'stats'  => ['fuerza', 'velocidad'],
                        'factor' => 0.8,
                    ],
                ]),
                'imagen'        => 'tecnicas_certeras.png',
            ],
            [
                'nombre'        => 'TELETRANSPORTARSE',//!!HECHO!!
                'descripcion'   => 'Reduce en un 100% el tiempo de viaje.',
                'modificadores' => json_encode([
                    [
                        'tipo'       => 'reduccion_tiempo_viaje',
                        'porcentaje' => 100,
                        'duracion'   => null,
                    ],
                ]),
                'imagen'        => 'teletransportarse.png',
            ],
            [
                'nombre'        => 'TRANCE',//!!HECHO!!
                'descripcion'   => '20% de chance de entrar en Trance. Un estado en el que se mejora en un 35% ATA y DEF.',
                'modificadores' => json_encode([
                    [
                        'tipo'           => 'estado_con_modificacion_stat',
                        'nombre_estado'  => 'Trance',
                        'chance'         => 20,
                        'modificaciones' => [
                            ['stat' => 'ataque', 'factor' => 1.35],
                            ['stat' => 'defensa', 'factor' => 1.35],
                        ],
                        'duracion'       => 5,
                    ],
                ]),
                'imagen'        => 'trance.png',
            ],
            [
                'nombre'        => 'REDUCCIÓN ELEMENTAL',//!!HECHO!!
                'descripcion'   => 'Reduce el daño elemental en un 50%.',
                'modificadores' => json_encode([
                    [
                        'tipo'       => 'reduccion_danio',
                        'porcentaje' => 50,
                        'tipo_danio' => 'elemental',
                        'duracion'   => 5,
                    ],
                ]),
                'imagen'        => 'reduccion_elemental.png',
            ],

            [
    'nombre'        => 'ANULACIÓN DE PODER',//!!HECHO!!
    'descripcion'   => 'Anula todos los poderes',
    'modificadores' => json_encode([
        [
            'tipo'    => 'anulacion_poder',
            'poderes' => [
                'ABSORVER SALUD',
                'ATAQUE DESESPERADO',
                'ATAQUE TRAICIONERO',
                'ATURDIR',
                'CAMUFLAJE',
                'COMBO VELOZ',
                'CONGELAR',
                'CONTROL CLIMATICO',
                'DAMAGE ABSORV',
                'DIRECT DAMAGE',
                'ENEMISTAD',
                'ENERGIZADO',
                'ENVENENAR',
                'ESPINAS',
                'FRENESÍ',
                'FURIA CIEGA',
                'GOLPES VELOCES',
                'HEMORRAGIA',
                'INSTINTO MEJORADO',
                'INTIMIDAR',
                'MÁXIMA POTENCIA',
                'MOLE',
                'OFENSIVO EXPERTO',
                'PARALIZAR',
                'PIEL DURA',
                'PIEL IMPENETRABLE',
                'QUEMAR',
                'REGENERAR SUPERIOR',
                'REGENERAR',
                'SIEMPRE EN PIE',
                'SUERTUDO',
                'ROBAR VIDA',
                'SANGRADO',
                'SUPER ATAQUE',
                'SUPER CARGA',
                'SUPER DEFENSA',
                'SUPER ENERGÍA',
                'SUPER FUERZA',
                'SUPER NOVA',
                'SUPER RESISTENCIA',
                'SUPER SENTIDOS',
                'SUPER VELOCIDAD',
                'TÉCNICAS CERTERAS',
                'TELETRANSPORTARSE',
                'TRANCE',
                'REDUCCIÓN ELEMENTAL',
            ],
        ],
    ]),
    'imagen' => 'anulacion_total.png',
],
        ];

        foreach ($poderes as $poder) {
            DB::table('poderes')->insert($poder);
        }
    }
}
