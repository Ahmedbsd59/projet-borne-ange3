import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import '../services/api_service.dart';

class JeuxScreen extends StatefulWidget {
  const JeuxScreen({super.key});

  @override
  State<JeuxScreen> createState() => _JeuxScreenState();
}

class _JeuxScreenState extends State<JeuxScreen> {
  List<dynamic> _jeux = [];
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() => _loading = true);
    try {
      final res = await ApiService.getJeux();
      if (res['success'] == true) setState(() => _jeux = res['jeux']);
    } catch (_) {}
    setState(() => _loading = false);
  }

  @override
  Widget build(BuildContext context) {
    return SafeArea(
      child: Padding(
        padding: const EdgeInsets.all(20),
        child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          Text('Les Jeux', style: GoogleFonts.poppins(
            fontSize: 24, fontWeight: FontWeight.bold, color: Colors.white)),
          Text('Découvrez nos jeux disponibles',
            style: GoogleFonts.poppins(fontSize: 13, color: Colors.white38)),
          const SizedBox(height: 24),
          _loading
              ? const Expanded(child: Center(child: CircularProgressIndicator(color: Color(0xFF7C3AED))))
              : Expanded(
                  child: RefreshIndicator(
                    onRefresh: _load,
                    color: const Color(0xFF7C3AED),
                    child: ListView.separated(
                      itemCount: _jeux.length,
                      separatorBuilder: (_, __) => const SizedBox(height: 14),
                      itemBuilder: (_, i) => _buildJeuCard(_jeux[i]),
                    ),
                  ),
                ),
        ]),
      ),
    );
  }

  Widget _buildJeuCard(Map<String, dynamic> jeu) {
    final colors = (jeu['colors'] as List).cast<String>();
    final dotations = (jeu['dotations'] as List?) ?? [];

    return Container(
      decoration: BoxDecoration(
        gradient: LinearGradient(
          colors: [_hexColor(colors[0]).withOpacity(0.15), _hexColor(colors[1]).withOpacity(0.05)],
        ),
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: _hexColor(colors[0]).withOpacity(0.3)),
      ),
      child: ExpansionTile(
        tilePadding: const EdgeInsets.symmetric(horizontal: 20, vertical: 8),
        childrenPadding: const EdgeInsets.fromLTRB(20, 0, 20, 16),
        leading: Text(jeu['icon'] ?? '🎮', style: const TextStyle(fontSize: 32)),
        title: Text(jeu['nom'],
          style: GoogleFonts.poppins(color: Colors.white, fontWeight: FontWeight.w600, fontSize: 16)),
        subtitle: Row(children: [
          _badge('${jeu['nb_parties']} parties', _hexColor(colors[0])),
          const SizedBox(width: 8),
          _badge('${jeu['taux_gain']}% gagnants', const Color(0xFF10B981)),
        ]),
        iconColor: Colors.white38,
        collapsedIconColor: Colors.white38,
        children: [
          if (dotations.isNotEmpty) ...[
            Text('Dotations disponibles',
              style: GoogleFonts.poppins(color: Colors.white54, fontSize: 12, fontWeight: FontWeight.w600)),
            const SizedBox(height: 8),
            ...dotations.map((d) => Padding(
              padding: const EdgeInsets.only(bottom: 6),
              child: Row(children: [
                Container(width: 8, height: 8, decoration: BoxDecoration(
                  color: _hexColor(d['couleur'] ?? '#7C3AED'),
                  shape: BoxShape.circle,
                )),
                const SizedBox(width: 10),
                Expanded(child: Text(d['libelle'],
                  style: GoogleFonts.poppins(color: Colors.white70, fontSize: 13))),
                Text('${d['probabilite']}%',
                  style: GoogleFonts.poppins(color: Colors.white38, fontSize: 12)),
              ]),
            )),
          ],
        ],
      ),
    );
  }

  Widget _badge(String label, Color color) => Container(
    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
    decoration: BoxDecoration(
      color: color.withOpacity(0.15),
      borderRadius: BorderRadius.circular(6),
    ),
    child: Text(label, style: GoogleFonts.poppins(color: color, fontSize: 11, fontWeight: FontWeight.w500)),
  );

  Color _hexColor(String hex) {
    final h = hex.replaceAll('#', '');
    return Color(int.parse('FF$h', radix: 16));
  }
}
