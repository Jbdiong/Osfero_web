
import 'package:flutter_test/flutter_test.dart';
import 'package:orbixsphere/models/event.dart';
import 'package:orbixsphere/models/to_do_list.dart';
import 'package:orbixsphere/models/renewal.dart';

void main() {
  group('Event Model Parsing', () {
    test('Should parse Laravel JSON correctly', () {
      final json = {
        'id': 1,
        'title': 'Test Event',
        'description': 'Description',
        'start_time': '2023-10-27T10:00:00Z',
        'end_time': '2023-10-27T12:00:00Z',
        'status_id': 5,
        'tenant_id': 1,
        'event_type': 'meeting',
        'importance': true,
        'user_id': 99,
        'participants': [],
      };

      final event = Event.fromJson(json);

      expect(event.title, 'Test Event');
      expect(event.description, 'Description');
      expect(event.startDate, isNotNull);
      expect(event.statusId, 5);
      expect(event.tenantId, 1);
    });
  });

  group('Todolist Model Parsing', () {
    test('Should parse Laravel JSON with Title and Description', () {
      final json = {
        'id': 100,
        'Title': 'Buy Groceries',
        'Description': 'Milk and Eggs',
        'status_id': 2,
        'tenant_id': 1,
        'start_date': '2023-10-27',
        'subtasks': [],
      };

      final todo = TodoListModel.fromJson(json);

      expect(todo.task, 'Buy Groceries');
      expect(todo.description, 'Milk and Eggs');
      expect(todo.statusId, 2);
    });
  });

  group('Renewal Model Parsing', () {
    test('Should parse Laravel JSON with Renew_Date', () {
      final json = {
        'id': 50,
        'LeadID': 10,
        'label': 'Hosting Renewal',
        'Renew_Date': '2024-01-01T00:00:00Z',
        'status_id': 1,
        'tenant_id': 1,
        'OldInvoiceID': 5,
        'Is_ads': 0,
        'Is_page': 0,
      };

      final renewal = Renewal.fromJson(json);

      expect(renewal.label, 'Hosting Renewal');
      expect(renewal.renewDate, isNotNull);
      expect(renewal.statusId, 1);
    });
  });
}
