import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';
import '../models/client.dart';

class ProfilScreen extends StatelessWidget {
  final Client client;
  final ClientStats stats;
  final VoidCallback onLogout;

  const ProfilScreen({
    super.key,
    required this.client,
    required this.stats,
    required this.onLogout,
  });

  @override
  Widget build(BuildContext context) {
    return SafeArea(
      child: SingleChildScrollView(
        padding: const EdgeInsets.all(20),
        child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          Text('Mon Profil', style: GoogleFonts.poppins(
            fontSize: 24, fontWeight: FontWeight.bold, color: Colors.white)),
          const SizedBox(height: 24),
          _buildAvatar(),
          const SizedBox(height: 24),
          _buildSection('Informations', [
            _buildRow(Icons.person_outline, 'Nom complet', client.fullName),
            _buildRow(Icons.email_outlined, 'Email', client.email),
            _buildRow(Icons.calendar_today_outlined, 'Membre depuis', client.dateInscription),
            _buildRow(Icons.credit_card, 'Badge RFID', client.rfidUid ?? 'Non associé'),
          ]),
          const SizedBox(height: 20),
          _buildSection('Mes performances', [
            _buildRow(Icons.military_tech, 'Rang', '${stats.rangEmoji} ${stats.rang}'),
            _buildRow(Icons.stars, 'Points', '${stats.points} pts'),
            _buildRow(Icons.sports_esports, 'Parties jouées', '${stats.total}'),
            _buildRow(Icons.emoji_events, 'Victoires', '${stats.wins}'),
            _buildRow(Icons.percent, 'Taux de gain', '${stats.taux.toStringAsFixed(1)}%'),
          ]),
          const SizedBox(height: 32),
          SizedBox(
            width: double.infinity,
            height: 52,
            child: OutlinedButton.icon(
              onPressed: onLogout,
              icon: const Icon(Icons.logout, color: Colors.redAccent),
              label: Text('Se déconnecter',
                style: GoogleFonts.poppins(color: Colors.redAccent, fontWeight: FontWeight.w600)),
              style: OutlinedButton.styleFrom(
                side: const BorderSide(color: Colors.redAccent),
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
              ),
            ),
          ),
        ]),
      ),
    );
  }

  Widget _buildAvatar() {
    return Center(
      child: Column(children: [
        Container(
          width: 90, height: 90,
          decoration: BoxDecoration(
            gradient: const LinearGradient(colors: [Color(0xFF7C3AED), Color(0xFFA855F7)]),
            shape: BoxShape.circle,
            boxShadow: [BoxShadow(
              color: const Color(0xFF7C3AED).withOpacity(0.4),
              blurRadius: 20, offset: const Offset(0, 8),
            )],
          ),
          child: Center(
            child: Text(client.prenom[0].toUpperCase(),
              style: GoogleFonts.poppins(fontSize: 36, fontWeight: FontWeight.bold, color: Colors.white)),
          ),
        ),
        const SizedBox(height: 12),
        Text(client.fullName, style: GoogleFonts.poppins(
          fontSize: 20, fontWeight: FontWeight.bold, color: Colors.white)),
        Text('${stats.rangEmoji} ${stats.rang}',
          style: GoogleFonts.poppins(fontSize: 14, color: Colors.white54)),
      ]),
    );
  }

  Widget _buildSection(String title, List<Widget> children) {
    return Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
      Text(title, style: GoogleFonts.poppins(
        fontSize: 15, fontWeight: FontWeight.w600, color: Colors.white54)),
      const SizedBox(height: 10),
      Container(
        decoration: BoxDecoration(
          color: const Color(0xFF1A1A2E),
          borderRadius: BorderRadius.circular(16),
        ),
        child: Column(children: children),
      ),
    ]);
  }

  Widget _buildRow(IconData icon, String label, String value) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
      child: Row(children: [
        Icon(icon, color: const Color(0xFF7C3AED), size: 20),
        const SizedBox(width: 14),
        Expanded(child: Text(label,
          style: GoogleFonts.poppins(color: Colors.white54, fontSize: 13))),
        Text(value, style: GoogleFonts.poppins(
          color: Colors.white, fontSize: 13, fontWeight: FontWeight.w500)),
      ]),
    );
  }
}
