import SwiftUI

/// Country-aware add/edit form for a local area. USA → city + state (+ optional
/// ZIP); international → city + country.
struct AreaSheet: View {
    let existing: Area?
    let onSubmit: (AreaRequest) -> Void

    @Environment(\.dismiss) private var dismiss

    @State private var country: String
    @State private var city: String
    @State private var state: String
    @State private var zip: String

    init(existing: Area?, onSubmit: @escaping (AreaRequest) -> Void) {
        self.existing = existing
        self.onSubmit = onSubmit
        _country = State(initialValue: existing?.countryCode ?? "US")
        _city = State(initialValue: existing?.locality ?? "")
        _state = State(initialValue: existing?.region ?? "")
        _zip = State(initialValue: existing?.postalCode ?? "")
    }

    private var isUs: Bool { country == "US" }
    private var valid: Bool { !city.trimmingCharacters(in: .whitespaces).isEmpty && (!isUs || !state.isEmpty) }

    var body: some View {
        NavigationStack {
            Form {
                Section {
                    Picker("Country", selection: $country) {
                        ForEach(GeoData.countries, id: \.code) { Text($0.name).tag($0.code) }
                    }
                    TextField(isUs ? "City (e.g. Cleveland)" : "City (e.g. Manchester)", text: $city)
                        .textInputAutocapitalization(.words)

                    if isUs {
                        Picker("State", selection: $state) {
                            Text("Choose a state…").tag("")
                            ForEach(GeoData.usStates, id: \.code) { Text($0.name).tag($0.code) }
                        }
                        TextField("ZIP (optional)", text: $zip)
                            .keyboardType(.numberPad)
                            .onChange(of: zip) { newValue in
                                zip = String(newValue.filter(\.isNumber).prefix(5))
                            }
                    }
                } footer: {
                    Text("Get news tailored to just this area.")
                }
            }
            .navigationTitle(existing == nil ? "Add local area" : "Edit local area")
            .navigationBarTitleDisplayMode(.inline)
            .toolbar {
                ToolbarItem(placement: .cancellationAction) {
                    Button("Cancel") { dismiss() }
                }
                ToolbarItem(placement: .confirmationAction) {
                    Button(existing == nil ? "Add" : "Save") {
                        onSubmit(AreaRequest(
                            countryCode: country,
                            city: city.trimmingCharacters(in: .whitespaces),
                            state: isUs ? state : nil,
                            zip: (isUs && !zip.isEmpty) ? zip : nil
                        ))
                        dismiss()
                    }
                    .disabled(!valid)
                }
            }
        }
    }
}
