<!DOCTYPE html>
<html lang="en" class="light">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Login | Energy Tracker Industrial</title>
    <script src="{{ asset('assets/js/tailwind.js') }}"></script>
    <link href="{{ asset('assets/css/local-fonts.css') }}" rel="stylesheet"/>
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#00628c",
                        "primary-container": "#007caf",
                        "surface": "#f7f9fc",
                        "surface-container-lowest": "#ffffff",
                        "on-surface": "#191c1e",
                        "on-surface-variant": "#3f484f",
                        "outline": "#6f7880",
                    },
                    fontFamily: {
                        "body": ["Inter"],
                    }
                },
            },
        }
    </script>
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-surface text-on-surface antialiased font-body min-h-screen flex items-center justify-center relative overflow-hidden">
    
    <!-- Background Accents -->
    <div class="absolute -top-40 -right-40 w-96 h-96 bg-primary-container/20 rounded-full blur-3xl"></div>
    <div class="absolute -bottom-40 -left-40 w-96 h-96 bg-sky-900/20 rounded-full blur-3xl"></div>
    
    <div class="w-full max-w-md px-6 py-12 bg-surface-container-lowest rounded-2xl shadow-[0_24px_40px_-4px_rgba(25,28,30,0.1)] border border-outline/10 relative z-10 transition-all">
        <!-- Logo Header -->
        <div class="flex flex-col items-center mb-10">
            <div class="h-16 w-16 bg-gradient-to-br from-sky-700 to-sky-950 rounded-xl shadow-lg flex items-center justify-center mb-6">
                <!-- Using an energy/bolt symbol as requested -->
                <span class="material-symbols-outlined text-white text-4xl">electric_bolt</span>
            </div>
            <h1 class="text-2xl font-extrabold tracking-tight text-on-surface uppercase mb-1">Energy Tracker</h1>
            <p class="text-on-surface-variant text-sm font-medium tracking-wide text-center">Peroni Karya Sentra Industrial Energy System</p>
        </div>

        <form action="{{ route('login.post') }}" method="POST" class="space-y-6">
            @csrf
            
            @if ($errors->any())
                <div class="bg-error-container text-on-error-container p-3 rounded-md text-sm mb-4">
                    {{ $errors->first() }}
                </div>
            @endif

            <div>
                <label for="email" class="block text-xs font-bold uppercase tracking-wide text-on-surface-variant mb-2">Email Address</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <span class="material-symbols-outlined text-outline/70">mail</span>
                    </div>
                    <input type="email" name="email" id="email" class="block w-full pl-10 pr-3 py-3 border border-outline/30 rounded-lg bg-surface text-on-surface focus:ring-2 focus:ring-primary focus:border-primary transition-all sm:text-sm shadow-sm" placeholder="user@peroniks.com" value="{{ old('email') }}" required>
                </div>
            </div>

            <div>
                <div class="flex items-center justify-between mb-2">
                    <label for="password" class="block text-xs font-bold uppercase tracking-wide text-on-surface-variant">Passcode</label>
                    <a href="#" class="text-xs font-bold text-primary hover:text-primary-container transition-colors">Forgot Device?</a>
                </div>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <span class="material-symbols-outlined text-outline/70">lock</span>
                    </div>
                    <input type="password" name="password" id="password" class="block w-full pl-10 pr-3 py-3 border border-outline/30 rounded-lg bg-surface text-on-surface focus:ring-2 focus:ring-primary focus:border-primary transition-all sm:text-sm shadow-sm" placeholder="••••••••">
                </div>
            </div>

            <div class="flex items-center">
                <input id="remember" name="remember" type="checkbox" class="h-4 w-4 text-primary focus:ring-primary border-outline/30 rounded bg-surface">
                <label for="remember" class="ml-2 block text-sm text-on-surface-variant font-medium">Keep session strictly active</label>
            </div>

            <button type="submit" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-md text-sm font-bold text-white bg-gradient-to-r from-sky-700 to-sky-900 hover:from-sky-600 hover:to-sky-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-all active:scale-[0.98]">
                Authenticate & Connect
            </button>
        </form>

        <div class="mt-8 pt-6 border-t border-outline/10 text-center">
            <p class="text-xs text-on-surface-variant/60 font-medium tracking-wide uppercase">
                Secure SSL connection established
            </p>
        </div>
    </div>
</body>
</html>
