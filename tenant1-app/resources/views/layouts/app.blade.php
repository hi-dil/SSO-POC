<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name', 'Tenant One') }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .header {
            background: rgba(255, 255, 255, 0.95);
            padding: 1rem 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .logo {
            font-size: 1.5rem;
            font-weight: bold;
            color: #667eea;
        }
        .nav-links {
            display: flex;
            gap: 1rem;
            align-items: center;
        }
        .nav-links a, .nav-links button {
            color: #667eea;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            transition: background 0.3s;
            border: none;
            background: none;
            cursor: pointer;
            font-size: 1rem;
        }
        .nav-links a:hover, .nav-links button:hover {
            background: rgba(102, 126, 234, 0.1);
        }
        .container {
            flex: 1;
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 2rem;
            width: 100%;
        }
        .alert {
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 5px;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
    @yield('styles')
</head>
<body>
    <header class="header">
        <div class="header-content">
            <div class="logo">{{ config('app.name', 'Tenant One') }}</div>
            <nav class="nav-links">
                @if(session('user'))
                    <span>Welcome, {{ session('user')['name'] ?? 'User' }}</span>
                    <a href="{{ route('dashboard') }}">Dashboard</a>
                    <div style="position: relative; display: inline-block;" id="logout-dropdown">
                        <button type="button" onclick="toggleLogoutDropdown()" style="background: none; border: none; color: #667eea; cursor: pointer; padding: 0.5rem 1rem; border-radius: 5px;">
                            Logout ▼
                        </button>
                        <div id="logout-options" style="display: none; position: absolute; right: 0; top: 100%; background: white; border: 1px solid #ccc; border-radius: 5px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); min-width: 200px; z-index: 1000;">
                            <form action="{{ route('logout') }}" method="POST" style="margin: 0;">
                                @csrf
                                <button type="submit" style="width: 100%; text-align: left; padding: 0.75rem 1rem; border: none; background: none; cursor: pointer; color: #333;">
                                    Logout from {{ config('app.name') }}
                                </button>
                            </form>
                            <form action="{{ route('logout') }}" method="POST" style="margin: 0;">
                                @csrf
                                <input type="hidden" name="sso_logout" value="1">
                                <button type="submit" style="width: 100%; text-align: left; padding: 0.75rem 1rem; border: none; background: none; cursor: pointer; color: #333; border-top: 1px solid #eee;">
                                    Logout from all apps
                                </button>
                            </form>
                        </div>
                    </div>
                @else
                    <a href="{{ route('login') }}">Login</a>
                    <a href="{{ route('register') }}">Register</a>
                @endif
            </nav>
        </div>
    </header>

    <main class="container">
        @if(session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        @if($errors->has('error'))
            <div class="alert alert-error">
                {{ $errors->first('error') }}
            </div>
        @endif

        @yield('content')
    </main>

    <script>
        function toggleLogoutDropdown() {
            const dropdown = document.getElementById('logout-options');
            dropdown.style.display = dropdown.style.display === 'none' ? 'block' : 'none';
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            const dropdown = document.getElementById('logout-dropdown');
            if (dropdown && !dropdown.contains(event.target)) {
                document.getElementById('logout-options').style.display = 'none';
            }
        });
    </script>
</body>
</html>