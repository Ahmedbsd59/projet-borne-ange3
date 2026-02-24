import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'services/api_service.dart';
import 'screens/login_screen.dart';
import 'screens/home_screen.dart';

void main() {
  WidgetsFlutterBinding.ensureInitialized();
  SystemChrome.setPreferredOrientations([DeviceOrientation.portraitUp]);
  SystemChrome.setSystemUIOverlayStyle(const SystemUiOverlayStyle(
    statusBarColor: Colors.transparent,
    statusBarIconBrightness: Brightness.light,
  ));
  runApp(const BorneApp());
}

class BorneApp extends StatelessWidget {
  const BorneApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'BorneInteract',
      debugShowCheckedModeBanner: false,
      theme: ThemeData(
        colorScheme: ColorScheme.fromSeed(
          seedColor: const Color(0xFF7C3AED),
          brightness: Brightness.dark,
        ),
        scaffoldBackgroundColor: const Color(0xFF0D0D14),
        useMaterial3: true,
      ),
      home: const AuthGate(),
    );
  }
}

class AuthGate extends StatefulWidget {
  const AuthGate({super.key});

  @override
  State<AuthGate> createState() => _AuthGateState();
}

class _AuthGateState extends State<AuthGate> {
  bool _checked = false;
  bool _loggedIn = false;

  @override
  void initState() {
    super.initState();
    _check();
  }

  Future<void> _check() async {
    final token = await ApiService.getToken();
    setState(() {
      _loggedIn = token != null && token.isNotEmpty;
      _checked = true;
    });
  }

  @override
  Widget build(BuildContext context) {
    if (!_checked) {
      return const Scaffold(
        backgroundColor: Color(0xFF0D0D14),
        body: Center(child: CircularProgressIndicator(color: Color(0xFF7C3AED))),
      );
    }
    if (_loggedIn) return const HomeScreen();
    return LoginScreen(onLogin: () => setState(() => _loggedIn = true));
  }
}
