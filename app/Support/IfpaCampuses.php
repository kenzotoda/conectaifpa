<?php

namespace App\Support;

class IfpaCampuses
{
    /**
     * Campi do IFPA, exceto Belém. Quem é do Belém entra como participante interno.
     *
     * @return list<string>
     */
    public static function otherThanBelem(): array
    {
        return [
            'Abaetetuba',
            'Altamira',
            'Ananindeua',
            'Bragança',
            'Breves',
            'Cametá',
            'Castanhal',
            'Conceição do Araguaia',
            'Itaituba',
            'Marabá Industrial',
            'Marabá Rural',
            'Óbidos',
            'Paragominas',
            'Parauapebas',
            'Santarém',
            'Tucuruí',
            'Vigia',
        ];
    }

    /**
     * @return list<string>
     */
    public static function institutionValues(): array
    {
        return array_map(
            fn (string $campus) => 'IFPA - '.$campus,
            self::otherThanBelem()
        );
    }
}
