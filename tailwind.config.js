const defaultTheme = require('tailwindcss/defaultTheme');
//const colors = require('tailwindcss/colors');

module.exports = {
    content: [
        './storage/framework/views/*.php', 
        './vendor/laravel/jetstream/**/*.blade.php',
        './resources/views/**/*.blade.php'],

    theme: {
        extend: {
            fontFamily: {
                //sans: ['Nunito', ...defaultTheme.fontFamily.sans],
                sans: ['Inter var', ...defaultTheme.fontFamily.sans],
            },
        },
        
    },



    plugins: [
    ]
};