<?php
    namespace App\Languages;

    class Language_Ru
    {
        public static function russionLanguage(): array
        {
            return [
                'greeting' => 'Здравствуйте!',
                'name_prompt' => 'Пожалуйста, введите ваше ФИО:',
                'data_saved' => '✅ Спасибо! Ваши данные сохранены:',
                'change_language' => 'Вы можете изменить язык в любой момент.'
            ];
        }
    }