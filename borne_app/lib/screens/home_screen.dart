import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import '../models/client.dart';
import '../services/api_service.dart';
import 'jeux_screen.dart';
import 'historique_screen.dart';
import 'profil_screen.dart';

class HomeScreen extends StatefulWidget {
  const HomeScreen({super.key});

  @override
  State<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen> {
  int _tab = 0;
  Client? _client;
  ClientStats? _stats;
  List<Partie> _parties = [];
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() => _loading = true);
    try {
      final res = await ApiService.getProfil();
      if (res['success'] == true) {
        setState(() {
          _client = Client.fromJson(res['client']);
          _stats = ClientStats.fromJson(res['stats']);
          _parties = (res['parties'] as List).map((p) => Partie.fromJson(p)).toList();
        });
      }
    } catch (_) {}
    setState(() => _loading = false);
  }

  @override
  Widget build(BuildContext context) {
    if (_loading) {
      return const Scaffold(
        backgroundColor: Color(0xFF0D0D14),
        body: Center(child: CircularProgressIndicator(color: Color(0xFF7C3AED))),
      );
    }

    if (_client == null || _stats == null) {
      return Scaffold(
        backgroundColor: const Color(0xFF0D0D14),
        body: Center(child: Column(mainAxisAlignment: MainAxisAlignment.center, children: [
          const Icon(Icons.wifi_off_rounded, color: Colors.white38, size: 64),
          const SizedBox(height: 16),
          Text('Impossible de charger le profil',
            style: GoogleFonts.poppins(color: Colors.white54, fontSize: 15)),
          const SizedBox(height: 24),
          ElevatedButton(
            onPressed: _load,
            style: ElevatedButton.styleFrom(backgroundColor: const Color(0xFF7C3AED)),
            child: Text('Réessayer', style: GoogleFonts.poppins(color: Colors.white)),
          ),
          const SizedBox(height: 12),
          TextButton(
            onPressed: _logout,
            child: Text('Se déconnecter', style: GoogleFonts.poppins(color: Colors.white38)),
          ),
        ])),
      );
    }

    final screens = [
      _buildAccueil(),
      const JeuxScreen(),
      HistoriqueScreen(parties: _parties),
      ProfilScreen(client: _client!, stats: _stats!, onLogout: _logout),
    ];

    return Scaffold(
      backgroundColor: const Color(0xFF0D0D14),
      body: screens[_tab],
      bottomNavigationBar: _buildNav(),
    );
  }

  Future<void> _logout() async {
    await ApiService.deleteToken();
    if (mounted) {
      Navigator.of(context).pushNamedAndRemoveUntil('/', (r) => false);
    }
  }

  Widget _buildAccueil() {
    return SafeArea(
      child: RefreshIndicator(
        onRefresh: _load,
        color: const Color(0xFF7C3AED),
        child: SingleChildScrollView(
          physics: const AlwaysScrollableScrollPhysics(),
          padding: const EdgeInsets.all(20),
          child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
            Row(mainAxisAlignment: MainAxisAlignment.spaceBetween, children: [
              Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                Text('Bonjour, ${_client?.prenom} 👋',
                  style: GoogleFonts.poppins(fontSize: 22, fontWeight: FontWeight.bold, color: Colors.white)),
                Text('Bienvenue sur BorneInteract',
                  style: GoogleFonts.poppins(fontSize: 13, color: Colors.white38)),
              ]),
              CircleAvatar(
                backgroundColor: const Color(0xFF7C3AED),
                radius: 24,
                child: Text(_client?.prenom[0].toUpperCase() ?? '?',
                  style: GoogleFonts.poppins(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 20)),
              ),
            ]),
            const SizedBox(height: 24),
            _buildLoyaltyCard(),
            const SizedBox(height: 24),
            Text('Mes statistiques',
              style: GoogleFonts.poppins(fontSize: 17, fontWeight: FontWeight.w600, color: Colors.white)),
            const SizedBox(height: 12),
            _buildStatsGrid(),
            const SizedBox(height: 24),
            Text('Dernières parties',
              style: GoogleFonts.poppins(fontSize: 17, fontWeight: FontWeight.w600, color: Colors.white)),
            const SizedBox(height: 12),
            ..._parties.take(3).map((p) => _buildPartieCard(p)),
          ]),
        ),
      ),
    );
  }

  Widget _buildLoyaltyCard() {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(24),
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          colors: [Color(0xFF7C3AED), Color(0xFF4F46E5)],
          begin: Alignment.topLeft, end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(20),
        boxShadow: [BoxShadow(color: const Color(0xFF7C3AED).withOpacity(0.4), blurRadius: 20, offset: const Offset(0, 8))],
      ),
      child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        Row(mainAxisAlignment: MainAxisAlignment.spaceBetween, children: [
          Text('Carte Fidélité', style: GoogleFonts.poppins(color: Colors.white70, fontSize: 13)),
          Text('${_stats?.rangEmoji} ${_stats?.rang}',
            style: GoogleFonts.poppins(color: Colors.white, fontWeight: FontWeight.w600)),
        ]),
        const SizedBox(height: 16),
        Text('${_stats?.points ?? 0}', style: GoogleFonts.poppins(
          fontSize: 42, fontWeight: FontWeight.bold, color: Colors.white)),
        Text('points', style: GoogleFonts.poppins(color: Colors.white70, fontSize: 14)),
        const SizedBox(height: 16),
        Text('${_client?.prenom} ${_client?.nom}'.toUpperCase(),
          style: GoogleFonts.poppins(color: Colors.white, fontWeight: FontWeight.w600, letterSpacing: 1.5)),
        if (_client?.rfidUid != null)
          Text('Badge RFID: ${_client!.rfidUid}',
            style: GoogleFonts.poppins(color: Colors.white54, fontSize: 11)),
      ]),
    );
  }

  Widget _buildStatsGrid() {
    final items = [
      {'label': 'Parties', 'value': '${_stats?.total ?? 0}', 'icon': Icons.sports_esports, 'color': const Color(0xFF7C3AED)},
      {'label': 'Victoires', 'value': '${_stats?.wins ?? 0}', 'icon': Icons.emoji_events, 'color': const Color(0xFF10B981)},
      {'label': 'Défaites', 'value': '${_stats?.losses ?? 0}', 'icon': Icons.close, 'color': const Color(0xFFEF4444)},
      {'label': 'Taux gain', 'value': '${_stats?.taux.toStringAsFixed(1) ?? 0}%', 'icon': Icons.percent, 'color': const Color(0xFFF59E0B)},
    ];
    return GridView.count(
      crossAxisCount: 2, shrinkWrap: true, physics: const NeverScrollableScrollPhysics(),
      crossAxisSpacing: 12, mainAxisSpacing: 12, childAspectRatio: 1.6,
      children: items.map((item) => Container(
        padding: const EdgeInsets.all(16),
        decoration: BoxDecoration(
          color: const Color(0xFF1A1A2E),
          borderRadius: BorderRadius.circular(16),
          border: Border.all(color: (item['color'] as Color).withOpacity(0.2)),
        ),
        child: Column(crossAxisAlignment: CrossAxisAlignment.start, mainAxisAlignment: MainAxisAlignment.center, children: [
          Icon(item['icon'] as IconData, color: item['color'] as Color, size: 22),
          const SizedBox(height: 8),
          Text(item['value'] as String, style: GoogleFonts.poppins(
            color: Colors.white, fontSize: 22, fontWeight: FontWeight.bold)),
          Text(item['label'] as String, style: GoogleFonts.poppins(color: Colors.white38, fontSize: 12)),
        ]),
      )).toList(),
    );
  }

  Widget _buildPartieCard(Partie p) {
    return Container(
      margin: const EdgeInsets.only(bottom: 10),
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
      decoration: BoxDecoration(
        color: const Color(0xFF1A1A2E),
        borderRadius: BorderRadius.circular(14),
      ),
      child: Row(children: [
        Text(p.icon, style: const TextStyle(fontSize: 24)),
        const SizedBox(width: 12),
        Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          Text(p.jeu, style: GoogleFonts.poppins(color: Colors.white, fontWeight: FontWeight.w500, fontSize: 14)),
          Text(p.date, style: GoogleFonts.poppins(color: Colors.white38, fontSize: 12)),
        ])),
        Container(
          padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
          decoration: BoxDecoration(
            color: p.gagne ? const Color(0xFF10B981).withOpacity(0.15) : const Color(0xFFEF4444).withOpacity(0.15),
            borderRadius: BorderRadius.circular(8),
          ),
          child: Text(p.gagne ? 'Gagné' : 'Perdu',
            style: GoogleFonts.poppins(
              color: p.gagne ? const Color(0xFF10B981) : const Color(0xFFEF4444),
              fontSize: 12, fontWeight: FontWeight.w600)),
        ),
      ]),
    );
  }

  Widget _buildNav() {
    final items = [
      {'icon': Icons.home_rounded, 'label': 'Accueil'},
      {'icon': Icons.sports_esports_rounded, 'label': 'Jeux'},
      {'icon': Icons.history_rounded, 'label': 'Historique'},
      {'icon': Icons.person_rounded, 'label': 'Profil'},
    ];
    return Container(
      decoration: const BoxDecoration(
        color: Color(0xFF1A1A2E),
        border: Border(top: BorderSide(color: Color(0xFF2A2A3E))),
      ),
      child: BottomNavigationBar(
        currentIndex: _tab,
        onTap: (i) => setState(() => _tab = i),
        backgroundColor: Colors.transparent,
        selectedItemColor: const Color(0xFF7C3AED),
        unselectedItemColor: Colors.white38,
        type: BottomNavigationBarType.fixed,
        elevation: 0,
        selectedLabelStyle: GoogleFonts.poppins(fontSize: 11, fontWeight: FontWeight.w600),
        unselectedLabelStyle: GoogleFonts.poppins(fontSize: 11),
        items: items.map((item) => BottomNavigationBarItem(
          icon: Icon(item['icon'] as IconData),
          label: item['label'] as String,
        )).toList(),
      ),
    );
  }
}
