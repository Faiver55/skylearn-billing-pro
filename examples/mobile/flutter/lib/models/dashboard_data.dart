/**
 * Dashboard data model
 */

class DashboardData {
  final DashboardStats stats;
  final List<dynamic> recentTransactions;
  final List<dynamic> activeSubscriptions;

  DashboardData({
    required this.stats,
    required this.recentTransactions,
    required this.activeSubscriptions,
  });

  factory DashboardData.fromJson(Map<String, dynamic> json) {
    return DashboardData(
      stats: DashboardStats.fromJson(json['stats'] ?? {}),
      recentTransactions: json['recent_transactions'] ?? [],
      activeSubscriptions: json['active_subscriptions'] ?? [],
    );
  }
}

class DashboardStats {
  final String totalSpent;
  final int activeSubscriptions;
  final int enrolledCourses;
  final String completionRate;

  DashboardStats({
    required this.totalSpent,
    required this.activeSubscriptions,
    required this.enrolledCourses,
    required this.completionRate,
  });

  factory DashboardStats.fromJson(Map<String, dynamic> json) {
    return DashboardStats(
      totalSpent: json['total_spent'] ?? '\$0.00',
      activeSubscriptions: json['active_subscriptions'] ?? 0,
      enrolledCourses: json['enrolled_courses'] ?? 0,
      completionRate: json['completion_rate'] ?? '0%',
    );
  }
}