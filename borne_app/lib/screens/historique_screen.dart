import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import '../models/client.dart';

class HistoriqueScreen extends StatefulWidget {
  final List<Partie> parties;
  const HistoriqueScreen({super.key, required this.parties});

  @override
  State<HistoriqueScreen> createState() => _HistoriqueScreenState();
}

class _HistoriqueScreenState extends State<HistoriqueScreen> {
  String _filtre = 'tous';

  List<Partie> get _filtered {
    switch (_filtre) {
      case 'gagné':   return widget.parties.where((p) => p.gagne).toList();
      case 'perdu':   return widget.parties.where((p) => !p.gagne).toList();
      case 'roue':    return widget.parties.where((p) => p.typeJeu == 'roue_chance').toList();
      case 'memory':  return widget.parties.where((p) => p.typeJeu == 'memory').toList();
      case 'jackpot': return widget.parties.where((p) => p.typeJeu == 'jackpot').toList();
      default:        return widget.parties;
    }
  }

  @override
  Widget build(BuildContext context) {
    return SafeArea(
      child: Padding(
        padding: const EdgeInsets.all(20),
        child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          Text('Historique', style: GoogleFonts.poppins(
            fontSize: 24, fontWeight: FontWeight.bold, color: Colors.white)),
          Text('${widget.parties.length} parties jouées',
            style: GoogleFonts.poppins(fontSize: 13, color: Colors.white38)),
          const SizedBox(height: 16),
          _buildFiltres(),
          const SizedBox(height: 16),
          Expanded(
            child: _filtered.isEmpty
                ? Center(child: Text('Aucune partie',
                    style: GoogleFonts.poppins(color: Colors.white38)))
                : ListView.separated(
                    itemCount: _filtered.length,
                    separatorBuilder: (_, __) => const SizedBox(height: 10),
                    itemBuilder: (_, i) => _buildCard(_filtered[i]),
                  ),
          ),
        ]),
      ),
    );
  }

  Widget _buildFiltres() {
    final filtres = [
      ('tous', 'Tout'), ('gagné', '✅ Gagnés'), ('perdu', '❌ Perdus'),
      ('roue', '🎡'), ('memory', '🧠'), ('jackpot', '🎰'),
    ];
    return SizedBox(
      height: 36,
      child: ListView(scrollDirection: Axis.horizontal, children: filtres.map((f) {
        final active = _filtre == f.$1;
        return GestureDetector(
          onTap: () => setState(() => _filtre = f.$1),
          child: Container(
            margin: const EdgeInsets.only(right: 8),
            padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 6),
            decoration: BoxDecoration(
              color: active ? const Color(0xFF7C3AED) : const Color(0xFF1A1A2E),
              borderRadius: BorderRadius.circular(20),
              border: Border.all(color: active ? const Color(0xFF7C3AED) : const Color(0xFF2A2A3E)),
            ),
            child: Text(f.$2, style: GoogleFonts.poppins(
              color: active ? Colors.white : Colors.white54,
              fontSize: 13, fontWeight: active ? FontWeight.w600 : FontWeight.normal)),
          ),
        );
      }).toList()),
    );
  }

  Widget _buildCard(Partie p) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: const Color(0xFF1A1A2E),
        borderRadius: BorderRadius.circular(14),
        border: Border.all(
          color: p.gagne ? const Color(0xFF10B981).withOpacity(0.2) : const Color(0xFFEF4444).withOpacity(0.2),
        ),
      ),
      child: Row(children: [
        Text(p.icon, style: const TextStyle(fontSize: 28)),
        const SizedBox(width: 14),
        Expanded(child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          Text(p.jeu, style: GoogleFonts.poppins(
            color: Colors.white, fontWeight: FontWeight.w600, fontSize: 14)),
          if (p.dotation != null && p.dotation!.isNotEmpty)
            Text(p.dotation!, style: GoogleFonts.poppins(color: Colors.white54, fontSize: 12)),
          Text(p.date, style: GoogleFonts.poppins(color: Colors.white38, fontSize: 11)),
        ])),
        Column(crossAxisAlignment: CrossAxisAlignment.end, children: [
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
          const SizedBox(height: 4),
          Text('+${p.gagne ? 50 : 10} pts',
            style: GoogleFonts.poppins(color: Colors.white38, fontSize: 11)),
        ]),
      ]),
    );
  }
}
