<?xml version="1.0" encoding="ISO-8859-1"?>
<StyledLayerDescriptor version="1.0.0" xmlns="http://www.opengis.net/sld" xmlns:ogc="http://www.opengis.net/ogc"
xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
xsi:schemaLocation="http://www.opengis.net/sld http://schemas.opengis.net/sld/1.0.0/StyledLayerDescriptor.xsd">
  <NamedLayer>
    <Name>Oberzentrum</Name>
    <UserStyle>
      <Name>Oberzentrum</Name>
      <Title>Oberzentrum</Title>
      <FeatureTypeStyle>        
        <Rule>
          <MaxScaleDenominator>4000000</MaxScaleDenominator>
          <PointSymbolizer uom="http://www.opengeospatial.org/se/units/metre">     
            <Graphic>
              <ExternalGraphic>
                <OnlineResource xlink:type="simple" xlink:href="images/ZVS_8511_1100.svg"/>
                <Format>image/svg+xml</Format>
              </ExternalGraphic>
              <Size><ogc:Literal>3500</ogc:Literal></Size>
              <Rotation>0</Rotation>
            </Graphic>     
          </PointSymbolizer>
        </Rule>        
      </FeatureTypeStyle>      
    </UserStyle>
  </NamedLayer>
</StyledLayerDescriptor>