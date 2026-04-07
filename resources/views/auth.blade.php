<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

        <!-- Styles / Scripts -->
        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @else
            
        @endif
    </head>
    <body class=" text-[#1b1b18] flex p-6 lg:p-8 items-center lg:justify-center min-h-screen flex-col" style="background-image: url('{{ asset('images/auth_background.jpg') }}'); background-size: cover; background-position: center;"> 
        
        <div class="flex items-center justify-center w-full transition-opacity opacity-100 duration-750 lg:grow starting:opacity-0">
            <main class="flex  w-1/4 flex-col-reverse  lg:flex-row">
                <div class="text-[13px] leading-[20px] flex-1 p-6  bg-white rounded-lg flex flex-col items-center">
                  <img src="{{ asset('images/orbixsphere_logo.png') }}" alt="Logo" class="object-contain p-12 mb-6">
                  <form action="{{ route('login') }}" method="POST" class="w-full">
                    @csrf
                    @if ($errors->any())
                      <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg">
                        <ul class="text-sm text-red-600">
                          @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                          @endforeach
                        </ul>
                      </div>
                    @endif
                    <div class="mb-4">
                      <input type="email" name="email" id="email" value="{{ old('email') }}" placeholder="Email" class="block w-full rounded-lg border border-gray-300 px-4 py-3 text-sm placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('email') border-red-500 @enderror">
                    </div>
                    <div class="mb-4">
                      <input type="password" name="password" id="password" placeholder="Password" class="block w-full rounded-lg border border-gray-300 px-4 py-3 text-sm placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('password') border-red-500 @enderror">
                    </div>
                    <div class="mb-6 flex items-center">
                      <input type="checkbox" name="remember" id="remember" class="w-4 h-4 border border-default-medium rounded-xs bg-neutral-secondary-medium focus:ring-2 focus:ring-brand-soft">
                      <label for="remember" class="ml-2 text-sm text-gray-600">Keep me logged in</label>
                    </div>
                    <button type="submit" class="w-full bg-[#0A84FF] text-white px-4 py-3 rounded-lg font-medium hover:bg-blue-700 transition-colors mb-4">Login</button>
                    <div class="text-center mb-6">
                      <a href="#" class="text-blue-600 text-sm hover:underline">Having problems?</a>
                    </div>
                    <div class="text-center text-sm text-gray-500">
                      Don't have an account? <a href="{{ Route::has('register') ? route('register') : '#' }}" class="text-blue-600 hover:underline">Sign up</a>
                    </div>
                  </form>
                </div>
                
            </main>
        </div>

        @if (Route::has('login'))
            <div class="h-14.5 hidden lg:block"></div>
        @endif
    </body>
</html>
