class Client {
  final int id;
  final String prenom;
  final String nom;
  final String email;
  final String? rfidUid;
  final String dateInscription;

  Client({
    required this.id,
    required this.prenom,
    required this.nom,
    required this.email,
    this.rfidUid,
    required this.dateInscription,
  });

  factory Client.fromJson(Map<String, dynamic> j) => Client(
        id: j['id'],
        prenom: j['prenom'],
        nom: j['nom'],
        email: j['email'],
        rfidUid: j['rfid_uid'],
        dateInscription: j['date_inscription'],
      );

  String get fullName => '$prenom $nom';
}

class ClientStats {
  final int total;
  final int wins;
  final int losses;
  final double taux;
  final int points;
  final String rang;

  ClientStats({
    required this.total,
    required this.wins,
    required this.losses,
    required this.taux,
    required this.points,
    required this.rang,
  });

  factory ClientStats.fromJson(Map<String, dynamic> j) {
    final rangRaw = j['rang'];
    final String rangStr = rangRaw is Map
        ? (rangRaw['label'] ?? 'Bronze')
        : rangRaw.toString();
    return ClientStats(
      total: j['total'],
      wins: j['wins'],
      losses: j['losses'],
      taux: (j['taux'] as num).toDouble(),
      points: j['points'],
      rang: rangStr,
    );
  }

  String get rangEmoji {
    switch (rang) {
      case 'Platine': return '💎';
      case 'Or':      return '🥇';
      case 'Argent':  return '🥈';
      default:        return '🥉';
    }
  }
}

class Partie {
  final int id;
  final String jeu;
  final String typeJeu;
  final bool gagne;
  final String? dotation;
  final String date;

  Partie({
    required this.id,
    required this.jeu,
    required this.typeJeu,
    required this.gagne,
    this.dotation,
    required this.date,
  });

  factory Partie.fromJson(Map<String, dynamic> j) => Partie(
        id: j['id'],
        jeu: j['jeu'],
        typeJeu: j['type_jeu'],
        gagne: j['gagne'] == true || j['gagne'] == 1,
        dotation: j['dotation'],
        date: j['date'],
      );

  String get icon {
    switch (typeJeu) {
      case 'roue_chance': return '🎡';
      case 'memory':      return '🧠';
      case 'jackpot':     return '🎰';
      default:            return '🎮';
    }
  }
}
