<?php

namespace App\Support;

/**
 * Cursos do Campus Belém publicados no Portal de Dados Abertos do IFPA
 * (conjunto "Cursos 2025", posição janeiro/2026). Turnos repetidos foram
 * unificados. Cursos FIC ficaram de fora.
 * Técnico em Aquicultura (Subsequente) não consta nesse conjunto, mas é
 * ofertado no campus (SIGAA, site do campus e PSU 2025/2026).
 */
class IfpaBelemCourses
{
    /**
     * @return array<string, list<string>>
     */
    public static function grouped(): array
    {
        return [
            'Graduação' => [
                'Análise e Desenvolvimento de Sistemas (Tecnologia)',
                'Ciências Biológicas (Licenciatura)',
                'Engenharia de Controle e Automação (Bacharelado)',
                'Engenharia de Materiais (Bacharelado)',
                'Engenharia Elétrica (Bacharelado)',
                'Física (Licenciatura)',
                'Geografia (Licenciatura)',
                'Gestão Hospitalar (Tecnologia)',
                'Gestão Pública (Tecnologia)',
                'História (Licenciatura)',
                'Letras - Língua Portuguesa (Licenciatura)',
                'Matemática (Licenciatura)',
                'Pedagogia (Licenciatura)',
                'Química (Licenciatura)',
            ],
            'Técnico integrado' => [
                'Técnico em Agrimensura (Integrado)',
                'Técnico em Desenvolvimento de Sistemas (Integrado)',
                'Técnico em Design de Interiores (Integrado)',
                'Técnico em Edificações (Integrado)',
                'Técnico em Eletrônica (Integrado)',
                'Técnico em Eletrotécnica (Integrado)',
                'Técnico em Estradas (Integrado)',
                'Técnico em Eventos (Integrado)',
                'Técnico em Mecânica (Integrado)',
                'Técnico em Metalurgia (Integrado)',
                'Técnico em Mineração (Integrado)',
                'Técnico em Química (Integrado)',
                'Técnico em Recursos Pesqueiros (Integrado)',
                'Técnico em Saneamento (Integrado)',
                'Técnico em Telecomunicações (Integrado)',
            ],
            'Técnico subsequente' => [
                'Técnico em Agente Comunitário de Saúde (Subsequente)',
                'Técnico em Agrimensura (Subsequente)',
                'Técnico em Aquicultura (Subsequente)',
                'Técnico em Design de Interiores (Subsequente)',
                'Técnico em Edificações (Subsequente)',
                'Técnico em Eletrônica (Subsequente)',
                'Técnico em Eletrotécnica (Subsequente)',
                'Técnico em Eventos (Subsequente)',
                'Técnico em Geodésia e Cartografia (Subsequente)',
                'Técnico em Guia de Turismo (Subsequente)',
                'Técnico em Hospedagem (Subsequente)',
                'Técnico em Mecânica (Subsequente)',
                'Técnico em Mineração (Subsequente)',
                'Técnico em Química (Subsequente)',
                'Técnico em Saneamento (Subsequente)',
                'Técnico em Segurança do Trabalho (Subsequente)',
                'Técnico em Telecomunicações (Subsequente)',
                'Técnico em Vigilância em Saúde (Subsequente)',
            ],
            'Pós-graduação' => [
                'Linguagens e Artes na Formação Docente (Especialização)',
                'Tecnologia Social em Saneamento, Saúde e Ambiente na Amazônia (Especialização)',
                'Mestrado Profissional em Educação Profissional e Tecnológica',
                'Mestrado Profissional em Engenharia de Materiais',
                'Mestrado Profissional em Propriedade Intelectual e Transferência de Tecnologia para Inovação',
            ],
        ];
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        $values = [];
        foreach (self::grouped() as $courses) {
            foreach ($courses as $course) {
                $values[] = $course;
            }
        }

        return $values;
    }
}
