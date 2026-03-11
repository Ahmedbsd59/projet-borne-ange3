import 'dart:convert';
import 'package:http/http.dart' as http;
import 'package:flutter_secure_storage/flutter_secure_storage.dart';

const String _baseUrl = 'https://raspberrypi.tailfaf4e9.ts.net/api';
const _storage = FlutterSecureStorage();

class ApiService {
  static Future<String?> getToken() => _storage.read(key: 'token');
  static Future<void> saveToken(String token) => _storage.write(key: 'token', value: token);
  static Future<void> deleteToken() => _storage.delete(key: 'token');

  static Future<Map<String, dynamic>> login(String email, String password) async {
    final res = await http.post(
      Uri.parse('$_baseUrl/mobile_login.php'),
      headers: {'Content-Type': 'application/json'},
      body: jsonEncode({'email': email, 'password': password}),
    );
    return jsonDecode(res.body);
  }

  static Future<Map<String, dynamic>> getProfil() async {
    final token = await getToken();
    final res = await http.get(
      Uri.parse('$_baseUrl/mobile_profil.php'),
      headers: {'Authorization': 'Bearer $token'},
    );
    return jsonDecode(res.body);
  }

  static Future<Map<String, dynamic>> getJeux() async {
    final res = await http.get(Uri.parse('$_baseUrl/jeux_public.php'));
    return jsonDecode(res.body);
  }
}
